<?php

namespace App\Service;

use App\Constants\AuditStatus;
use App\Constants\AuditType;
use App\Constants\PostType;
use App\Exception\LogicException;
use App\Exception\ParametersException;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class PostsService
{
    #[Inject]
    protected AuditService $auditService;

    public function getList(array $params): array
    {
        $params['has_circle'] = true;
        $query = $this->buildQuery($params);
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

    public function getApiList(array $params, bool $is_paginate = true, int $limit = 0): array
    {
        if(!empty($params['post_type']) && $params['post_type'] == PostType::DYNAMIC){
            $cate = ['cover', 'is_like'];
            $columns = ['post.id', 'post.title', 'post.content', 'post.post_type', 'post.media',
                'post.audit_status', 'post.circle_id', 'post.view_count', 'post.comment_count','post.like_count',
                'post.user_id', 'user.nickname', 'post.audit_status', 'post.create_time'];
        }else{
            $cate =  ['cover'];
            $params['has_circle'] = true;
            $columns = ['post.id', 'post.title', 'post.content', 'post.post_type', 'post.media',
                'post.audit_status', 'post.circle_id', 'circle.name as circle_name', 'post.view_count', 'post.comment_count','post.like_count',
                'post.user_id', 'user.nickname', 'post.audit_status', 'post.create_time'];
        }
        $query = $this->buildQuery($params);
        $query = $query->select($columns);
        // TODO 前端接口综合排序 并且需要可考虑下架 投诉等
        $query->orderBy('post.create_time', 'desc');
        if($is_paginate){
            $page = !empty($params['page']) ? $params['page'] : 1;
            $page_size = !empty($params['page_size']) ? $params['page_size'] : 15;
            $data = $query->paginate((int)$page_size, page: (int)$page);
            $data = paginateTransformer($data);
            if (!empty($data['items'])) {
                foreach ($data['items'] as $key => $item) {
                    $this->objectTransformer($item, $cate, $params);
                }
            }
        }else{
            if($limit){
                $data = $query->limit($limit)->get()->toArray();
            }else{
                $data = $query->get()->toArray();
            }
            foreach ($data as $item) {
                $this->objectTransformer($item, $cate, $params);
            }
        }
        return $data;
    }

    protected function buildQuery(array $params)
    {
        $query = Db::table('post')
            ->leftJoin('user', 'user.id', '=', 'post.user_id');
        if(!empty($params['has_circle'])){
            $query->leftJoin('circle', 'circle.id', '=', 'post.circle_id');
        }
        if(!empty($params['is_follow'])){
            $query->leftJoin('user_follow', 'user_follow.follow_id', '=', 'post.user_id')
                ->where('user_follow.user_id', '=', $params['current_user_id'] ?? 0);
        }
        if (!empty($params['title'])) {
            $query->where('post.title', 'like', '%' . $params['title'] . '%');
        }
        if (!empty($params['keyword'])) {
            $query->where('post.title', 'like', '%' . $params['keyword'] . '%');
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
        if (!empty($params['source'])) {
            $query->where('post.source', '=', $params['source']);
        }
        if (!empty($params['start_time']) && !empty($params['end_time'])) {
            $query->whereBetween('post.create_time', [$params['start_time'], $params['end_time']]);
        }
        if(isset($params['is_reported'])){
            $query->where('is_reported', '=', 0);
        }
        $query->where('del_flag', '=', 0);
        return $query;
    }

    public function getInfo(int $post_id, array $cate = [], int $user_id = 0): object
    {
        $post = Db::table('post')
            ->leftJoin('circle', 'circle.id', '=', 'post.circle_id')
            ->leftJoin('user', 'user.id', '=', 'post.user_id')
            ->where('post.id', '=', $post_id)
            ->select(['post.id', 'post.title', 'post.content', 'post.media', 'post.post_type',
                'post.audit_status', 'post.audit_result', 'post.circle_id', 'circle.cover as circle_cover', 'circle.name as circle_name',
                'post.user_id', 'user.avatar as user_avatar', 'user.nickname', 'post.audit_status', 'post.create_time'])
            ->first();
        if (!$post) {
            throw new LogicException('帖子不存在');
        }
        $this->objectTransformer($post, $cate, ['current_user_id' => $user_id]);
        return $post;
    }

    protected function objectTransformer(object $item, array $cate = [], array $params = [])
    {
        if (property_exists($item, 'user_avatar')) {
            $item->user_avatar = getAvatar($item->user_avatar);
        }
        if (property_exists($item, 'circle_cover')) {
            $item->circle_cover = generateFileUrl($item->circle_cover);
        }
        if (property_exists($item, 'media')) {
            $item->media_urls = generateMulFileUrl($item->media);
        }
        if (in_array('cover', $cate)) {
            $item->cover = $item->media_urls[0] ?? [];
        }
        if (in_array('is_like', $cate)) {
            if (isset($params['like_ids'])) {
                $item->is_like = in_array($item->id, $params['like_ids']) ? 1 : 0;
            } else {
                $item->is_like = $this->checkIsLike($item->id, $params['current_user_id'] ?? 0);
            }
        }
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
            $this->auditService->pass(AuditType::POST->value, $post_id, $cur_user_id);
            Db::commit();
        } catch (\Throwable $ex) {
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
            $this->auditService->reject(AuditType::POST->value, $post_id, $cur_user_id, $reject_reason);
            Db::commit();
        } catch (\Throwable $ex) {
            Db::rollBack();
            throw new LogicException($ex->getMessage());
        }
        return true;
    }

    public function publish(int $user_id, array $params, $source = 'user'): int
    {
        return Db::table('post')->insertGetId([
            'user_id' => $user_id,
            'circle_id' => $params['circle_id'],
            'title' => $params['title'],
            'content' => $params['content'],
            'post_type' => $params['post_type'],
            'media' => empty($params['media']) ? '' : implode(',', $params['media']),
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
            'source' => $source,
            'audit_status' => $source == 'user' ? AuditStatus::PENDING->value : AuditStatus::PASSED->value,
        ]);
    }

    public function like(int $post_id, int $user_id, int $status): bool
    {
        $has = Db::table('post_like')
            ->where(['post_id' => $post_id, 'user_id' => $user_id])
            ->count();
        if ((empty($has) && $status == 0) || (!empty($has) && $status == 1)) {
            return true;
        }
        Db::beginTransaction();
        try {
            if ($status == 1) {
                $res1 = Db::table('post_like')->insert([
                    'post_id' => $post_id,
                    'user_id' => $user_id,
                    'create_time' => date('Y-m-d H:i:s'),
                ]);
                $res2 = Db::table('post')->where('id', '=', $post_id)->increment('like_count');
            } else {
                $res1 = Db::table('post_like')
                    ->where(['post_id' => $post_id, 'user_id' => $user_id])
                    ->delete();
                $res2 = Db::table('post')->where('id', '=', $post_id)->decrement('like_count');
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

    public function checkIsLike(int $post_id, int $user_id): int
    {
        if (empty($user_id) || empty($post_id)) {
            return 0;
        }
        $has = Db::table('post_like')
            ->where(['post_id' => $post_id, 'user_id' => $user_id])
            ->count();
        return $has > 0 ? 1 : 0;
    }


}