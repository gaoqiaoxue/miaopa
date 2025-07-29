<?php

namespace App\Service;

use App\Constants\MessageCate;
use App\Constants\PostType;
use App\Constants\ReferType;
use Hyperf\DbConnection\Db;

class MessageService
{
    public function addSystemMessage(int $user_id, int $cate, string $message, int $refer_id = 0, int $refer_type = 0, int $refer_uid = 0)
    {
        $data = [
            'user_id' => $user_id,
            'group' => 'system',
            'title' => '系统消息',
            'message' => $message,
            'cate' => $cate,
            'refer_id' => $refer_id,
            'refer_type' => $refer_type,
            'refer_uid' => $refer_uid,
            'is_read' => 0,
            'create_time' => date('Y-m-d H:i:s')
        ];
        return Db::table('user_message')->insert($data);
    }

    public function addLikeMessage(int $user_id, int $cate, int $refer_type = 0, int $refer_id = 0, int $refer_uid = 0)
    {
        $data = [
            'user_id' => $user_id,
            'group' => 'like',
            'title' => '',
            'message' => '',
            'cate' => $cate,
            'refer_id' => $refer_id,
            'refer_type' => $refer_type,
            'refer_uid' => $refer_uid,
            'is_read' => 0,
            'create_time' => date('Y-m-d H:i:s')
        ];
        return Db::table('user_message')->insert($data);
    }

    public function addFansMessage(int $user_id, int $fans_id)
    {
        $data = [
            'user_id' => $user_id,
            'group' => 'fans',
            'title' => '',
            'message' => '',
            'cate' => MessageCate::FANS->value,
            'refer_id' => $fans_id,
            'refer_type' => 0,
            'refer_uid' => $fans_id,
            'is_read' => 0,
            'create_time' => date('Y-m-d H:i:s')
        ];
        return Db::table('user_message')->insert($data);
    }

    public function addCommentMessage(int $to_user_id, int $user_id, int $comment_id, MessageCate $cate = MessageCate::COMMENT)
    {
        $data = [
            'user_id' => $to_user_id,
            'group' => 'comment',
            'title' => '',
            'message' => '',
            'cate' =>$cate->value,
            'refer_id' => $comment_id,
            'refer_type' => ReferType::COMMENT->value,
            'refer_uid' => $user_id,
            'is_read' => 0,
            'create_time' => date('Y-m-d H:i:s')
        ];
        return Db::table('user_message')->insert($data);
    }

    public function getSystemMessage(int $user_id, array $params = [], $paginate = true, $limit = 10)
    {
        $list = $this->getMessages('system', $user_id, $params, $paginate, $limit);
        return $list;
    }

    // 获取详情，需要带帖子/评论详情
    public function getLikeMessage(int $user_id, array $params = [], $paginate = true, $limit = 10): array
    {
        $list = $this->getMessages('like', $user_id, $params, $paginate, $limit);
        foreach ($list['items'] as $item) {
            $item->user_info = generalAPiUserInfo($item);
            if ($item->refer_type == ReferType::COMMENT->value) {
                $content = Db::table('comment')
                    ->where('id', $item->refer_id)
                    ->where('is_reported', 0)
                    ->where('del_flag', 0)
                    ->select(['id', 'post_type', 'post_id', 'answer_id', 'parent_id', 'content', 'images'])
                    ->first();
                if($content){
                    $content->images = generateMulFileUrl($content->images);
                }
            } else {
                $content = Db::table('post')
                    ->where('id', $item->refer_id)
                    ->where('is_reported', 0)
                    ->where('del_flag', 0)
                    ->select(['id', 'post_type', 'title', 'content', 'media_type', 'media'])
                    ->first();
                if($content){
                    $content->media = generateMulFileUrl($content->media);
                }
            }
            list($content_type, $message) = $this->getContentType('like', $item->refer_type, $content);
            $item->content_type = $content_type;
            $item->content = $content;
            $item->message = $message;
        }
        return $list;
    }

    public function getFansMessage(int $user_id, array $params = [], $paginate = true, $limit = 10): array
    {
       $list = $this->getMessages('fans', $user_id, $params, $paginate, $limit);
       $user_ids = array_column($list['items'], 'user_id');
       $follow_ids = Db::table('user_follow')
           ->whereIn('follow_id', $user_ids)
           ->where('user_id', $user_id)
           ->pluck('follow_id')
           ->toArray();
        foreach ($list['items'] as $item) {
            $item->message = '关注了您';
            $item->user_info = generalAPiUserInfo($item);
            $item->is_follow = in_array($item->user_id, $follow_ids) ? 1 : 0;
        }
        return $list;
    }

    public function getCommentMessage(int $user_id, array $params = [], $paginate = true, $limit = 10): array
    {
        $list = $this->getMessages('comment', $user_id, $params, $paginate, $limit);
        $items = $list['items'] ?: [];
        $comment_ids = array_column($items, 'refer_id');
        $comments = Db::table('comment')
            ->whereIn('id', $comment_ids)
            ->select(['id', 'post_type', 'post_id', 'answer_id', 'parent_id', 'content', 'images'])
            ->get()
            ->toArray();
        $comments = array_column($comments, null, 'id');
        foreach ($list['items'] as $item) {
            $item->user_info = generalAPiUserInfo($item);
            $content = $comments[$item->refer_id] ?? [];
            $item->content = $content;
            list($content_type, $message) = $this->getContentType('like', $item->refer_type, $content);
            $item->content_type = $content_type;
            $item->message = $message;
        }
        return $list;
    }

    protected function getMessages(string $group, int $user_id, array $params = [], $paginate = true, $limit = 10)
    {
        $query = Db::table('user_message');
        $field = ['user_message.id', 'user_message.title', 'user_message.message',
            'user_message.refer_id', 'user_message.refer_type', 'user_message.refer_uid as user_id',
            'user_message.is_read', 'user_message.create_time'];
        if($group != 'system'){
            $query->leftJoin('user', 'user.id', '=', 'user_message.refer_uid');
            $field = array_merge($field, ['user.nickname', 'user.avatar', 'user.show_icon', 'user.avatar_icon']);
        }
        $query->where('user_message.user_id', $user_id)
            ->where('user_message.group', $group)
            ->select( $field)
            ->orderBy('user_message.id', 'desc');
        if ($paginate) {
            $page = empty($params['page']) ? 1 : $params['page'];
            $page_size = empty($params['page_size']) ? 15 : $params['page_size'];
            $list = $query->paginate((int)$page_size, page: (int)$page);
            $list = paginateTransformer($list);
            // 将查询出的数据全部转为已读
            $ids = array_column($list['items'], 'id');
            $this->setRead($user_id,['ids' => $ids]);
        } else {
            if (!empty($limit)) $query->limit($limit);
            $items = $query->get()->toArray();
            $list['items'] = $items;
        }
        return $list;
    }

    protected function getContentType(string $action, int $refer_type, object|null $content)
    {
        $content_type = '';
        $message = '';
        if(empty($content)){
            return [$content_type, $message];
        }
        if ($refer_type == ReferType::COMMENT->value) {
            $content_type = 'comment';
            $message = $action == 'like' ? '点赞了您的评论' : '回复了您的评论';
            if($content->post_type == PostType::QA->value){
                if(empty($content->answer_id)){
                    $content_type = 'answer';
                    $message = $action == 'like' ? '点赞了您的回答' : '评论了您的回答';
                }elseif(empty($content->parent_id)){
                    $content_type = 'comment';
                    $message = $action == 'like' ? '点赞了您的评论' : '回复了您的评论';
                }else{
                    $content_type = 'reply';
                    $message = $action == 'like' ? '点赞了您的回复' : '回复了您的评论';
                }
            }elseif($content->post_type == PostType::DYNAMIC->value){
                if(empty($content->parent_id)){
                    $content_type = 'comment';
                    $message = $action == 'like' ? '点赞了您的评论' : '回复了您的评论';
                }else{
                    $content_type = 'reply';
                    $message = $action == 'like' ? '点赞了您的回复' : '回复了您的评论';
                }
            }
        } else {
            $content_type = 'post';
            $message = $action == 'like' ? '点赞了您的动态' : '评论了您的动态';
            if($content->post_type == PostType::QA->value){
                $message = $action == 'like' ? '点赞了您的提问' : '回答了您的提问';
            }
        }
        return [$content_type, $message];
    }
    public function getMessageList(int $user_id)
    {
        $unread = Db::table('user_message')
            ->where('user_id', $user_id)
            ->where('is_read', 0)
            ->groupBy('group')
            ->select(['group', Db::raw('count(*) as total')])
            ->get()
            ->toArray();
        $group_count = array_column($unread, 'total', 'group');
        return [
            'system' => [
                'total' => $group_count['system'] ?? 0,
                'list' => $this->getSystemMessage($user_id, [], false, 1)['items'][0] ?? []
            ],
            'like' => [
                'total' => $group_count['like'] ?? 0,
                'list' => $this->getLikeMessage($user_id, [], false, 1)['items'][0] ?? []
            ],
            'fans' => [
                'total' => $group_count['fans'] ?? 0,
                'list' => $this->getFansMessage($user_id, [], false, 1)['items'][0] ?? []
            ],
            'comment' => [
                'total' => $group_count['comment'] ?? 0,
                'list' => $this->getCommentMessage($user_id, [], false, 1)['items'][0] ?? []
            ],
            'total_unread' => array_sum($group_count),
        ];
    }

    public function setRead(int $user_id, array $params = [])
    {
        var_dump($params);
        $query = Db::table('user_message')
            ->where('user_id', $user_id)
            ->where('is_read', 0);
        if(!empty($params['group'])){
            $query->where('group', $params['group']);
        }
        if(!empty($params['ids'])){
            $query->whereIn('id', $params['ids']);
        }
        $query->update(['is_read' => 1, 'read_time' => date('Y-m-d H:i:s')]);
    }

}