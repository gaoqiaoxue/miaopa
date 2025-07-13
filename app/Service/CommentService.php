<?php

namespace App\Service;

use App\Constants\AuditStatus;
use App\Constants\AuditType;
use App\Constants\PostType;
use App\Exception\LogicException;
use App\Exception\ParametersException;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class CommentService
{
    #[Inject]
    protected AuditService $auditService;

    public function getList(array $params): array
    {
        $query = Db::table('comment')
            ->leftJoin('user', 'user.id', '=', 'comment.user_id');
        if (!empty($params['nickname'])) {
            $query->where('user.nickname', 'like', '%' . $params['nickname'] . '%');
        }
        if (!empty($params['user_id'])) {
            $query->where('comment.user_id', '=', $params['user_id']);
        }
        if (!empty($params['start_time']) && !empty($params['end_time'])) {
            $query->whereBetween('comment.create_time', [$params['start_time'], $params['end_time']]);
        }
        if (!empty($params['post_id'])) {
            $query->where('comment.post_id', '=', $params['post_id']);
        }
        if (!empty($params['source'])) {
            $query->where('comment.source', '=', $params['source']);
        }
        $page = !empty($params['page']) ? $params['page'] : 1;
        $page_size = !empty($params['page_size']) ? $params['page_size'] : 15;
        $data = $query->select(['comment.id', 'comment.content', 'comment.create_time', 'comment.user_id', 'user.nickname'])
            ->orderBy('comment.create_time', 'desc')
            ->paginate((int)$page_size, page: (int)$page);
        $data = paginateTransformer($data);
        return $data;
    }

    public function getInfo(int $comment_id): object
    {
        $comment = Db::table('comment')
            ->leftJoin('user', 'user.id', '=', 'comment.user_id')
            ->where(['comment.id' => $comment_id])
            ->select(['comment.id', 'comment.post_id', 'comment.content', 'comment.images', 'comment.reply_count', 'comment.create_time', 'comment.user_id', 'user.nickname'])
            ->first();
        if (!$comment) {
            throw new LogicException('评论不存在');
        }
        $comment->image_urls = generateMulFileUrl($comment->images);
        return $comment;
    }

    public function delete(int $comment_id): int
    {
        return Db::table('comment')->where('id', $comment_id)->update([
            'del_flag' => 1,
            'update_time' => date('Y-m-d H:i:s')
        ]);
    }

    public function setTop(int $comment_id, int $is_top): int
    {
        return Db::table('comment')->where('id', $comment_id)->update([
            'is_top' => $is_top,
            'update_time' => date('Y-m-d H:i:s')
        ]);
    }

    public function pass(int $comment_id, int $cur_user_id): bool
    {
        $comment = Db::table('comment')
            ->where('id', '=', $comment_id)
            ->first(['id', 'source', 'audit_status']);
        if (empty($comment)) {
            throw new LogicException('帖子不存在');
        }
        if ($comment->audit_status != AuditStatus::PENDING->value) {
            throw new LogicException('帖子已经审核过了');
        }
        Db::beginTransaction();
        try {
            Db::table('comment')->where('id', '=', $comment_id)->update([
                'audit_status' => AuditStatus::PASSED->value,
                'update_time' => date('Y-m-d H:i:s'),
            ]);
            $this->auditService->pass(AuditType::COMMENT->value, $comment_id, $cur_user_id);
            Db::commit();
        } catch (\Throwable $ex) {
            Db::rollBack();
            throw new LogicException($ex->getMessage());
        }
        return true;
    }

    public function reject(int $comment_id, int $cur_user_id, string $reject_reason)
    {
        $comment = Db::table('comment')
            ->where('id', '=', $comment_id)
            ->first(['id', 'audit_status']);
        if (empty($comment)) {
            throw new LogicException('帖子不存在');
        }
        if ($comment->audit_status != AuditStatus::PENDING->value) {
            throw new LogicException('该角色已经审核过了');
        }
        Db::beginTransaction();
        try {
            Db::table('comment')->where('id', '=', $comment_id)->update([
                'audit_status' => AuditStatus::REJECTED->value,
                'audit_result' => $reject_reason,
            ]);
            $this->auditService->reject(AuditType::COMMENT->value, $comment_id, $cur_user_id, $reject_reason);
            Db::commit();
        } catch (\Throwable $ex) {
            Db::rollBack();
            throw new LogicException($ex->getMessage());
        }
        return true;
    }

    // 评论
    public function comment(int $user_id, int $post_id, string $content, array $images = [])
    {
        $post = Db::table('post')->where(['id' => $post_id])->first(['id', 'post_type']);
        if (empty($post)) {
            throw new LogicException('帖子不存在');
        }
        Db::beginTransaction();
        try {
            $comment_id = Db::table('comment')->insertGetId([
                'user_id' => $user_id,
                'post_id' => $post_id,
                'post_type' => $post->post_type,
                'content' => $content,
                'images' => empty($images) ? '' : implode(',', $images),
                'audit_status' => AuditStatus::PENDING->value,
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s')
            ]);
            $this->auditService->addAuditRecord(AuditType::COMMENT->value, $comment_id, $user_id);
            Db::table('post')->where('id', $post_id)->increment('comment_count', 1);
            Db::commit();
        } catch (\Throwable $ex) {
            Db::rollBack();
            throw new LogicException($ex->getMessage());
        }
        return true;
    }

    // 回复
    public function reply(int $user_id, int $parent_id, string $content, array $images = [])
    {
        $comment = Db::table('comment')
            ->where(['id' => $parent_id])
            ->first(['id', 'user_id', 'post_id', 'post_type', 'parent_id', 'answer_id']);
        if (empty($comment)) {
            throw new LogicException('评论不存在');
        }
        if ($comment->post_type == PostType::QA->value) {
            if (empty($comment->answer_id)) { // 回答的评论
                $answer_id = $parent_id;
                $this_parent_id = 0;
                $at_user_id = 0;
            } else { //评论回复
                $answer_id = $comment->answer_id;
                if (empty($comment->parent_id)) { // 一级回复
                    $this_parent_id = $parent_id;
                    $at_user_id = 0;
                } else { // 多级回复
                    $this_parent_id = $comment->parent_id;
                    $at_user_id = $comment->user_id;
                }
            }
        } else {
            $answer_id = 0;
            if (empty($comment->parent_id)) { // 一级回复
                $this_parent_id = $parent_id;
                $at_user_id = 0;
            } else { // 多级回复
                $this_parent_id = $comment->parent_id;
                $at_user_id = $comment->user_id;
            }
        }
        Db::beginTransaction();
        try {
            $comment_id = Db::table('comment')->insertGetId([
                'user_id' => $user_id,
                'post_id' => $comment->post_id,
                'post_type' => $comment->post_type,
                'parent_id' => $this_parent_id,
                'answer_id' => $answer_id,
                'at_user_id' => $at_user_id,
                'content' => $content,
                'images' => empty($images) ? '' : implode(',', $images),
                'audit_status' => AuditStatus::PENDING->value,
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s')
            ]);
            $this->auditService->addAuditRecord(AuditType::COMMENT->value, $comment_id, $user_id);
            Db::table('comment')->where('id', $comment->post_id)->increment('reply_count', 1);
            Db::commit();
        } catch (\Throwable $ex) {
            Db::rollBack();
            throw new LogicException($ex->getMessage());
        }
        return true;
    }

    public function getCommentList(array $params, int $user_id, array $cate = []): array
    {
        $query = Db::table('comment')
            ->leftJoin('user', 'user.id', '=', 'comment.user_id')
            ->where(['comment.del_flag' => 0, 'comment.is_reported' => 0])
            ->where('comment.parent_id', '=', 0);
        if (!empty($params['post_id'])) {
            $query->where('comment.post_id', $params['post_id']);
        }
        if (!empty($params['answer_id'])) {
            $query->where('comment.answer_id', $params['answer_id']);
        }
        $page = empty($params['page']) ? 1 : intval($params['page']);
        $page_size = empty($params['page_size']) ? 10 : intval($params['page_size']);
        $data = $query->select(['comment.id', 'comment.post_id', 'comment.content', 'comment.images', 'comment.reply_count', 'comment.create_time',
            'comment.user_id', 'user.nickname', 'user.avatar as user_avatar'])
            ->orderBy('comment.create_time', 'desc')
            ->paginate((int)$page_size, page: (int)$page);
        $data = paginateTransformer($data);
        $comment_ids = array_column($data['items'], 'id');
        $like_ids = $this->getUserCommentLikes($user_id, $comment_ids);
        foreach ($data['items'] as $item) {
            $this->objectTransformer($item, $cate, ['user_id' => $user_id, 'like_ids' => $like_ids]);
        }
        return $data;
    }

    public function getReplyList(array $params, int $user_id, array $cate = []): array
    {
        $query = Db::table('comment')
            ->leftJoin('user', 'user.id', '=', 'comment.user_id')
            ->where(['comment.del_flag' => 0, 'comment.is_reported' => 0])
            ->where('comment.parent_id', '=', 0);
        if (!empty($params['comment_id'])) {
            $query->where('comment.parent_id', $params['comment_id']);
        }
        $page = empty($params['page']) ? 1 : intval($params['page']);
        $page_size = empty($params['page_size']) ? 10 : intval($params['page_size']);
        $data = $query->select(['comment.id', 'comment.post_id', 'comment.content', 'comment.images', 'comment.reply_count', 'comment.create_time',
            'comment.user_id', 'user.nickname', 'user.avatar as user_avatar'])
            ->orderBy('comment.create_time', 'desc')
            ->paginate((int)$page_size, page: (int)$page);
        $data = paginateTransformer($data);
        $comment_ids = array_column($data['items'], 'id');
        $like_ids = $this->getUserCommentLikes($user_id, $comment_ids);
        foreach ($data['items'] as $item) {
            $this->objectTransformer($item, $cate, ['user_id' => $user_id, 'like_ids' => $like_ids]);
        }
        return $data;
    }

    public function getCommentDetail(int $comment_id, int $user_id): object
    {
        $comment = Db::table('comment')
            ->leftJoin('user', 'user.id', '=', 'comment.user_id')
            ->where(['comment.id' => $comment_id])
            ->select(['comment.id', 'comment.post_id', 'comment.content', 'comment.images', 'comment.reply_count', 'comment.create_time',
                'comment.user_id', 'user.nickname', 'user.avatar as user_avatar'])
            ->first();
        if (!$comment) {
            throw new LogicException('评论不存在');
        }
        $this->objectTransformer($comment, ['is_like'], ['user_id' => $user_id]);
        return $comment;
    }

    protected function objectTransformer(object $item, array $cate = [], array $params = [])
    {
        if (property_exists($item, 'user_avatar')) {
            $item->user_avatar = getAvatar($item->user_avatar);
        }
        if (property_exists($item, 'images')) {
            $item->images = empty($item->images) ? [] : explode(',', $item->images);
            $item->image_urls = generateMulFileUrl($item->images);
        }
        if (in_array('is_like', $cate)) {
            if(isset($params['like_ids'])){
                $item->is_like = in_array($item->id, $params['like_ids']) ? 1 : 0;
            }else{
                $item->is_like = $this->checkIsLike($item->id, $params['user_id'] ?? 0);
            }
        }
    }

    public function like(int $comment_id, int $user_id, int $status): bool
    {
        $has = Db::table('comment_like')
            ->where(['comment_id' => $comment_id, 'user_id' => $user_id])
            ->count();
        if ((empty($has) && $status == 0) || (!empty($has) && $status == 1)) {
            return true;
        }
        Db::beginTransaction();
        try {
            if ($status == 1) {
                $res1 = Db::table('comment_like')->insert([
                    'comment_id' => $comment_id,
                    'user_id' => $user_id,
                    'create_time' => date('Y-m-d H:i:s'),
                ]);
                $res2 = Db::table('comment')->where('id', '=', $comment_id)->increment('like_count');
            } else {
                $res1 = Db::table('comment_like')
                    ->where(['comment_id' => $comment_id, 'user_id' => $user_id])
                    ->delete();
                $res2 = Db::table('comment')->where('id', '=', $comment_id)->decrement('like_count');
            }
            if (!$res1 || !$res2) {
                throw new LogicException('操作失败');
            }
            Db::commit();
        } catch (\Throwable $ex) {
            Db::rollBack();
            throw new ParametersException($ex->getMessage());
        }
        return true;
    }

    public function checkIsLike(int $comment_id, int $user_id): int
    {
        if (empty($user_id) || empty($comment_id)) {
            return 0;
        }
        $has = Db::table('comment_like')
            ->where(['comment_id' => $comment_id, 'user_id' => $user_id])
            ->count();
        return $has > 0 ? 1 : 0;
    }

    public function getUserCommentLikes(int $user_id, array $comment_ids): array
    {
        if(empty($comment_ids) || empty($user_id)) {
            return [];
        }
        return Db::table('comment_like')
            ->where(['user_id' => $user_id])
            ->whereIn('comment_id', $comment_ids)
            ->pluck('comment_id')
            ->toArray();

    }
}