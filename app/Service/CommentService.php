<?php

namespace App\Service;

use App\Exception\LogicException;
use App\Exception\ParametersException;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class CommentService
{
    #[Inject]
    protected FileService $fileService;

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
            ->select(['comment.id', 'comment.post_id', 'comment.content', 'comment.images', 'comment.create_time', 'comment.user_id','user.nickname'])
            ->first();
        if (!$comment) {
            throw new LogicException('评论不存在');
        }
        if(!empty($comment->images)){
            $images = explode(',', $comment->images);
            $comment->image_urls = $this->fileService->getFilepathByIds($images);
        }else{
            $comment->image_urls = [];
        }
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
}