<?php

namespace App\Service;

use App\Constants\AuditStatus;
use App\Constants\AuditType;
use App\Constants\CoinCate;
use App\Constants\MessageCate;
use App\Constants\PostType;
use App\Constants\PrestigeCate;
use App\Constants\ReferType;
use App\Exception\LogicException;
use App\Exception\ParametersException;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class CommentService
{
    #[Inject]
    protected AuditService $auditService;

    #[Inject]
    protected CreditService $creditService;

    #[Inject]
    protected MessageService $messageService;

    #[Inject]
    protected VirtualService $virtualService;

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
        $data = $query->select(['comment.id', 'comment.content', 'comment.is_top', 'comment.reply_count', 'comment.create_time', 'comment.user_id', 'user.nickname'])
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
            ->select(['comment.id', 'comment.post_id', 'comment.content', 'comment.images', 'comment.is_top', 'comment.reply_count', 'comment.create_time', 'comment.user_id', 'user.nickname'])
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
            ->first(['id', 'user_id', 'post_id', 'parent_id', 'answer_id', 'at_user_id', 'source', 'audit_status']);
        if (empty($comment)) {
            throw new LogicException('评论不存在');
        }
        if ($comment->audit_status != AuditStatus::PENDING->value) {
            throw new LogicException('评论已经审核过了');
        }
        Db::beginTransaction();
        try {
            Db::table('comment')->where('id', '=', $comment_id)->update([
                'audit_status' => AuditStatus::PASSED->value,
                'update_time' => date('Y-m-d H:i:s'),
            ]);
            $this->auditService->pass(AuditType::COMMENT->value, $comment_id, $cur_user_id);
            if (empty($comment->parent_id) && empty($comment->answer_id)) {
                $post = Db::table('post')->where(['id' => $comment->post_id])->first(['id', 'user_id']);
                $this->commentSuccess($comment->user_id, $comment_id, $post->id, $post->user_id);
            } else {
                $parent_id = empty($comment->parent_id) ? $comment->answer_id : $comment->parent_id;
                if (!empty($comment->at_user_id)) {
                    $be_comment_uid = $comment->at_user_id;
                } else {
                    $be_comment_uid = Db::table('comment')->where(['id' => $parent_id])->value('user_id');
                }
                $this->replySuccess($comment->user_id, $comment_id, $parent_id, $be_comment_uid);
            }
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
        $post = Db::table('post')->where(['id' => $post_id])->first(['id', 'user_id', 'post_type']);
        if (empty($post)) {
            throw new LogicException('帖子不存在');
        }
        Db::beginTransaction();
        try {
            $comment = [
                'user_id' => $user_id,
                'post_id' => $post_id,
                'post_type' => $post->post_type,
                'content' => $content,
                'images' => empty($images) ? '' : implode(',', $images),
                'audit_status' => AuditStatus::PENDING->value,
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s')
            ];
            $comment_id = Db::table('comment')->insertGetId($comment);
            $this->auditService->addAuditRecord(AuditType::COMMENT->value, $comment_id, $user_id);
            Db::commit();
        } catch (\Throwable $ex) {
            Db::rollBack();
            throw new LogicException($ex->getMessage());
        }
        $this->pass($comment_id, 1); // TODO 自动审核通过
        return true;
    }

    // 评论成功奖励
    protected function commentSuccess(int $user_id, int $comment_id, int $post_id, int $post_user_id)
    {
        Db::table('post')->where('id', $post_id)->increment('comment_count', 1);
        // 金币奖励
        $this->creditService->finishCoinTask($user_id, CoinCate::COMMENT, $comment_id, '参与讨论');
        // 声望
        $this->creditService->finishPrestigeTask($post_user_id, PrestigeCate::BE_COMMENTED, $comment_id, '被回复', ReferType::COMMENT->value, $user_id);
        $this->creditService->finishPrestigeTask($user_id, PrestigeCate::COMMENT, $comment_id, '回复', ReferType::COMMENT->value);
        // 用户消息
        $this->messageService->addCommentMessage($post_user_id, $user_id, $comment_id);
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
                $at_parent_id = 0;
                $at_user_id = 0;
            } else { //评论回复
                $answer_id = $comment->answer_id;
                if (empty($comment->parent_id)) { // 一级回复
                    $this_parent_id = $parent_id;
                    $at_parent_id = 0;
                    $at_user_id = 0;
                } else { // 多级回复
                    $this_parent_id = $comment->parent_id;
                    $at_parent_id = $comment->id;
                    $at_user_id = $comment->user_id;
                }
            }
        } else {
            $answer_id = 0;
            if (empty($comment->parent_id)) { // 一级回复
                $this_parent_id = $parent_id;
                $at_parent_id = 0;
                $at_user_id = 0;
            } else { // 多级回复
                $this_parent_id = $comment->parent_id;
                $at_parent_id = $comment->id;
                $at_user_id = $comment->user_id;
            }
        }
        Db::beginTransaction();
        try {
            $current_comment = [
                'user_id' => $user_id,
                'post_id' => $comment->post_id,
                'post_type' => $comment->post_type,
                'parent_id' => $this_parent_id,
                'answer_id' => $answer_id,
                'at_parent_id' => $at_parent_id,
                'at_user_id' => $at_user_id,
                'content' => $content,
                'images' => empty($images) ? '' : implode(',', $images),
                'audit_status' => AuditStatus::PENDING->value,
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s')
            ];
            $comment_id = Db::table('comment')->insertGetId($current_comment);
            $this->auditService->addAuditRecord(AuditType::COMMENT->value, $comment_id, $user_id);
            Db::commit();
        } catch (\Throwable $ex) {
            Db::rollBack();
            throw new LogicException($ex->getMessage());
        }
        $this->pass($comment_id, 1); // TODO 自动审核通过
        return true;
    }

    // 回复成功奖励
    protected function replySuccess(int $user_id, int $comment_id, int $parent_id, int $be_commented_uid)
    {
        Db::table('comment')->where('id', $parent_id)->increment('reply_count', 1);
        // 金币奖励
        $this->creditService->finishCoinTask($user_id, CoinCate::COMMENT, $comment_id, '参与讨论');
        // 声望
        $this->creditService->finishPrestigeTask($be_commented_uid, PrestigeCate::BE_COMMENTED, $comment_id, '被回复', ReferType::COMMENT->value, $user_id);
        $this->creditService->finishPrestigeTask($user_id, PrestigeCate::COMMENT, $comment_id, '回复', ReferType::COMMENT->value);
        // 用户消息
        $this->messageService->addCommentMessage($be_commented_uid, $user_id, $comment_id, MessageCate::REPLY);
    }

    public function getCommentList(array $params, int $user_id, array $cate = []): array
    {
        $query = Db::table('comment')
            ->leftJoin('user', 'user.id', '=', 'comment.user_id')
            ->where(['comment.del_flag' => 0, 'comment.is_reported' => 0, 'comment.audit_status' => AuditStatus::PASSED->value])
            ->where('comment.parent_id', '=', 0);
        if (!empty($params['post_id'])) {
            $query->where('comment.post_id', $params['post_id']);
        }
        if (isset($params['answer_id'])) {
            $query->where('comment.answer_id', $params['answer_id']);
        }
        $page = empty($params['page']) ? 1 : intval($params['page']);
        $page_size = empty($params['page_size']) ? 15 : intval($params['page_size']);
        $data = $query->select(['comment.id', 'comment.post_id', 'comment.content', 'comment.images',
            'comment.reply_count', 'comment.like_count', 'comment.create_time',
            'comment.user_id', 'user.nickname', 'user.avatar as user_avatar', 'user.show_icon', 'user.avatar_icon'])
            ->orderBy('comment.is_top', 'desc')
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

    public function getReplyList(array $params, int $user_id = 0, array $cate = ['is_like', 'at_user']): array
    {
        $query = Db::table('comment')
            ->leftJoin('user', 'user.id', '=', 'comment.user_id')
            ->where(['comment.del_flag' => 0, 'comment.is_reported' => 0, 'comment.audit_status' => AuditStatus::PASSED->value])
            ->where('comment.parent_id', $params['comment_id']);
        $start = empty($params['next_num']) ? 0 : intval($params['next_num']);
        $limit = empty($params['limit']) ? 10 : intval($params['limit']);
        $list = $query->select(['comment.id', 'comment.post_id', 'comment.content', 'comment.images',
            'comment.reply_count', 'comment.like_count', 'comment.create_time', 'comment.at_user_id',
            'comment.user_id', 'user.nickname', 'user.avatar as user_avatar', 'user.show_icon', 'user.avatar_icon'])
            ->orderBy('comment.create_time', 'desc')
            ->limit($limit)
            ->offset($start)
            ->get()
            ->toArray();
        $comment_ids = array_column($list, 'id');
        $like_ids = $this->getUserCommentLikes($user_id, $comment_ids);
        $at_user_ids = array_column($list, 'at_user_id');
        $at_user_ids = array_unique($at_user_ids);
        $at_users = Db::table('user')->whereIn('id', $at_user_ids)->get(['id', 'nickname', 'avatar'])->toArray();
        $at_users = array_column($at_users, null, 'id');
        foreach ($at_users as $user) {
            $user->avatar = getAvatar($user->avatar);
        }
        foreach ($list as $item) {
            $item->at_user = $at_users[$item->at_user_id] ?? null;
            $this->objectTransformer($item, $cate, ['user_id' => $user_id, 'like_ids' => $like_ids]);
        }
        return ['next_num' => count($list) == $limit ? $start + $limit : 0, 'items' => $list];
    }

    public function getCommentDetail(int $comment_id, int $user_id): object
    {
        $comment = Db::table('comment')
            ->leftJoin('user', 'user.id', '=', 'comment.user_id')
            ->where(['comment.id' => $comment_id])
            ->select(['comment.id', 'comment.post_id', 'comment.content', 'comment.images',
                'comment.reply_count', 'comment.like_count', 'comment.create_time',
                'comment.user_id', 'user.nickname', 'user.avatar as user_avatar', 'user.show_icon', 'user.avatar_icon'])
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
            $item->user_info = [
                'id' => $item->user_id,
                'avatar' => getAvatar($item->user_avatar),
                'nickname' => $item->nickname,
                'show_icon' => $item->show_icon,
                'avatar_icon' => $item->show_icon ? (!empty($item->avatar_icon) ? generateFileUrl($item->avatar_icon) : $this->virtualService->getDefaultAvatarIcon()) : '',
            ];
        }
        if (property_exists($item, 'images')) {
            $item->images = empty($item->images) ? [] : explode(',', $item->images);
            $item->image_urls = generateMulFileUrl($item->images);
        }
        if (in_array('is_like', $cate)) {
            if (isset($params['like_ids'])) {
                $item->is_like = in_array($item->id, $params['like_ids']) ? 1 : 0;
            } else {
                $item->is_like = $this->checkIsLike($item->id, $params['user_id'] ?? 0);
            }
        }
        if (in_array('reply', $cate)) {
            if ($item->reply_count == 0) {
                $item->reply = ['next_num' => 0, 'data' => []];
            }
            $item->reply = $this->getReplyList(['comment_id' => $item->id, 'limit' => 1], $params['user_id'] ?? 0);
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
        $comment = Db::table('comment')->where(['id' => $comment_id])->first(['id', 'user_id', 'content']);
        if (empty($comment)) {
            throw new LogicException('评论不存在');
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
                $this->likeSuccess($user_id, $comment);
            } else {
                $res1 = Db::table('comment_like')
                    ->where(['comment_id' => $comment_id, 'user_id' => $user_id])
                    ->delete();
                $res2 = Db::table('comment')->where('id', '=', $comment_id)->decrement('like_count');
            }
            if (!$res1 || !$res2) {
                Db::rollBack();
                throw new LogicException('操作失败');
            }
            Db::commit();
        } catch (\Throwable $ex) {
            Db::rollBack();
            throw new ParametersException($ex->getMessage());
        }
        return true;
    }

    // 点赞成功奖励
    protected function likeSuccess(int $user_id, object $comment)
    {
        // 声望
        $this->creditService->finishPrestigeTask($comment->user_id, PrestigeCate::BE_LIKED, $comment->id, '获赞', ReferType::COMMENT->value, $user_id);
        $this->creditService->finishPrestigeTask($user_id, PrestigeCate::LIKE, $comment->id, '点赞', ReferType::COMMENT->value);
        // 用户消息
        $this->messageService->addLikeMessage($comment->user_id, MessageCate::COMMENT_LIKE->value, ReferType::COMMENT->value, $comment->id, $user_id);
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
        if (empty($comment_ids) || empty($user_id)) {
            return [];
        }
        return Db::table('comment_like')
            ->where(['user_id' => $user_id])
            ->whereIn('comment_id', $comment_ids)
            ->pluck('comment_id')
            ->toArray();

    }

    public function answerShare(int $user_id, int $answer_id): bool
    {
        Db::beginTransaction();
        try {
            Db::table('post_share')->insert([
                'user_id' => $user_id,
                'answer_id' => $answer_id,
                'create_time' => date('Y-m-d H:i:s'),
            ]);
            $this->creditService->finishPrestigeTask($user_id, PrestigeCate::SHARE, $answer_id, '答案分享', ReferType::COMMENT->value);
            Db::commit();
        } catch (\Throwable $ex) {
            Db::rollBack();
            throw new ParametersException($ex->getMessage());
        }
        return true;
    }
}