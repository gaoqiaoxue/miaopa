<?php

namespace App\Service;

use App\Constants\AuditStatus;
use App\Exception\LogicException;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class PostsService
{
    #[Inject]
    protected FileService $fileService;

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
        if(!empty($params['nickname'])){
            $query->where('user.nickname', 'like', '%' . $params['nickname'] . '%');
        }
        if(isset($params['audit_status']) && in_array($params['audit_status'], AuditStatus::getKeys())) {
            $query->where('post.status', '=', $params['status']);
        }
        if (!empty($params['start_time']) && !empty($params['end_time'])) {
            $query->whereBetween('post.create_time', [$params['start_time'], $params['end_time']]);
        }
        $page = !empty($params['page']) ? $params['page'] : 1;
        $page_size = !empty($params['page_size']) ? $params['page_size'] : 15;
        $data = $query->select(['post.id', 'post.title','post.content', 'post.post_type', 'post.comment_count', 'post.circle_id', 'circle.name as circle_name', 'post.user_id', 'user.nickname', 'post.audit_status', 'post.create_time'])
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
            ->select(['post.id', 'post.title', 'post.content', 'post.media', 'post.post_type', 'post.circle_id', 'circle.name as circle_name', 'post.user_id', 'user.nickname', 'post.audit_status', 'post.create_time'])
            ->first();
        if (!$post) {
            throw new LogicException('帖子不存在');
        }
        if(!empty($post->media)){
            $media = explode(',', $post->media);
            $post->media_urls = $this->fileService->getFileInfoByIds($media);
        }else{
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
}