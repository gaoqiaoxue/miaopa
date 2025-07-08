<?php

namespace App\Service;

use App\Constants\AuditStatus;
use App\Constants\AuditType;
use App\Exception\LogicException;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class PostsService
{
    #[Inject]
    protected FileService $fileService;

    #[Inject]
    protected AuditService $auditService;

    public function getList(array $params): array
    {
        $query = Db::table('post')
            ->leftJoin('circle', 'circle.id', '=', 'post.circle_id')
            ->leftJoin('user', 'user.id', '=', 'post.user_id');
        if (!empty($params['title'])) {
            $query->where('post.title', 'like', '%' . $params['title'] . '%');
        }
        if (!empty($params['post_type'])) {
            $query->where('post.post_type', '=', $params['post_type']);
        }
        if (!empty($params['circle_id'])) {
            $query->where('post.circle_id', '=', $params['circle_id']);
        }
        if (!empty($params['circle_name'])) {
            $query->where('circle.name', 'like', '%' . $params['circle_name'] . '%');
        }
        if (!empty($params['user_id'])) {
            $query->where('post.user_id', '=', $params['user_id']);
        }
        if (!empty($params['nickname'])) {
            $query->where('user.nickname', 'like', '%' . $params['nickname'] . '%');
        }
        if (isset($params['audit_status']) && in_array($params['audit_status'], AuditStatus::getKeys())) {
            $query->where('post.audit_status', '=', $params['audit_status']);
        }
        if(!empty($params['source'])){
            $query->where('post.source', '=', $params['source']);
        }
        if (!empty($params['start_time']) && !empty($params['end_time'])) {
            $query->whereBetween('post.create_time', [$params['start_time'], $params['end_time']]);
        }
        $page = !empty($params['page']) ? $params['page'] : 1;
        $page_size = !empty($params['page_size']) ? $params['page_size'] : 15;
        $data = $query->select(['post.id', 'post.title', 'post.content', 'post.post_type', 'post.comment_count',
            'post.audit_status', 'post.circle_id', 'circle.name as circle_name',
            'post.user_id', 'user.nickname', 'post.audit_status', 'post.create_time'])
            ->orderBy('post.create_time', 'desc')
            ->paginate((int)$page_size, page: (int)$page);
        $data = paginateTransformer($data);
        return $data;

    }

    public function getInfo(int $post_id): object
    {
        $post = Db::table('post')
            ->leftJoin('circle', 'circle.id', '=', 'post.circle_id')
            ->leftJoin('user', 'user.id', '=', 'post.user_id')
            ->where('post.id', '=', $post_id)
            ->select(['post.id', 'post.title', 'post.content', 'post.media', 'post.post_type',
                'post.audit_status', 'post.audit_result', 'post.circle_id', 'circle.name as circle_name',
                'post.user_id', 'user.nickname', 'post.audit_status', 'post.create_time'])
            ->first();
        if (!$post) {
            throw new LogicException('帖子不存在');
        }
        if (!empty($post->media)) {
            $media = explode(',', $post->media);
            $post->media_urls = array_values($this->fileService->getFileInfoByIds($media));
        } else {
            $post->media_urls = [];
        }
        return $post;
    }

    public function delete(int $post_id): int
    {
        return Db::table('post')->where('id', $post_id)->update([
            'del_flag' => 1,
            'update_time' => date('Y-m-d H:i:s')
        ]);
    }

    public function pass(int $post_id, int $cur_user_id): bool
    {
        $post = Db::table('post')
            ->where('id', '=', $post_id)
            ->first(['id', 'source', 'audit_status']);
        if ($post->audit_status != AuditStatus::PENDING->value) {
            throw new LogicException('帖子已经审核过了');
        }
        Db::beginTransaction();
        try {
            Db::table('post')->where('id', '=', $post_id)->update([
                'audit_status' => AuditStatus::PASSED->value,
                'update_time' => date('Y-m-d H:i:s'),
            ]);
            $this->auditService->pass(AuditType::POST->value,$post_id,$cur_user_id);
            Db::commit();
        }catch (\Throwable $ex) {
            Db::rollBack();
            throw new LogicException($ex->getMessage());
        }
        return true;
    }

    public function reject(int $post_id, int $cur_user_id, string $reject_reason)
    {
        $post = Db::table('post')
            ->where('id', '=', $post_id)
            ->first(['id', 'audit_status']);
        if ($post->audit_status != AuditStatus::PENDING->value) {
            throw new LogicException('该角色已经审核过了');
        }
        Db::beginTransaction();
        try {
            Db::table('post')->where('id', '=', $post_id)->update([
                'audit_status' => AuditStatus::REJECTED->value,
                'audit_result' => $reject_reason,
            ]);
            $this->auditService->reject(AuditType::POST->value,$post_id,$cur_user_id,$reject_reason);
            Db::commit();
        }catch (\Throwable $ex) {
            Db::rollBack();
            throw new LogicException($ex->getMessage());
        }
        return true;
    }

    public function publish(int $user_id, array $params, $source = 'user'):int
    {
        return Db::table('post')->insertGetId([
            'user_id' => $user_id,
            'circle_id' => $params['circle_id'],
            'title' => $params['title'],
            'content' => $params['content'],
            'post_type' => $params['post_type'],
            'media' => empty($params['media']) ? '' : implode(',',$params['media']),
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
            'source' => $source,
            'audit_status' => $source == 'user' ? AuditStatus::PENDING->value : AuditStatus::PASSED->value,
        ]);
    }

    public function addViewRecord(int $user_id, int $post_id)
    {
        Db::beginTransaction();
        try {
            Db::table('post')->where('id', $post_id)->increment('view_count');
            Db::table('post_view_record')->insert([
                'user_id' => $user_id,
                'post_id' => $post_id,
                'create_time' => date('Y-m-d H:i:s'),
            ]);
            Db::commit();
        } catch (\Throwable $ex) {
            Db::rollBack();
            throw new LogicException($ex->getMessage());
        }
    }
}