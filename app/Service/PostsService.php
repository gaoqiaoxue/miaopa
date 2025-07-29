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
use Carbon\Carbon;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class PostsService
{
    #[Inject]
    protected AuditService $auditService;

    #[Inject]
    protected CreditService $creditService;

    #[Inject]
    protected MessageService $messageService;

    public function getList(array $params): array
    {
        $params['has_circle'] = true;
        $query = $this->buildQuery($params);
        $page = !empty($params['page']) ? $params['page'] : 1;
        $page_size = !empty($params['page_size']) ? $params['page_size'] : 15;
        $data = $query->select(['post.id', 'post.title', 'post.content', 'post.post_type', 'post.comment_count',
            'post.audit_status', 'post.circle_id', 'circle.name as circle_name',
            'post.user_id', 'user.nickname', 'post.audit_status', 'post.create_time', 'post.del_flag'])
            ->orderBy('post.create_time', 'desc')
            ->paginate((int)$page_size, page: (int)$page);
        $data = paginateTransformer($data);
        return $data;

    }


    #[Cacheable(prefix: 'post_recomment_max_value', ttl: 600)]
    protected function getPostRecommendMax(): object
    {
        $maxValues = Db::table('post')
            ->select([
                Db::raw('MAX(view_count) as max_views'),
                Db::raw('MAX(like_count) as max_likes'),
                Db::raw('MAX(comment_count) as max_comments'),
                Db::raw('UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(MIN(create_time)) as max_time_diff')
            ])
            ->where('audit_status', 1)
            ->where('is_reported', 0)
            ->where('del_flag', 0)
            ->first();
        return $maxValues;
    }

    public function getApiList(array $params, bool $is_paginate = true, int $limit = 0, bool $recommendOrder = false): array
    {
        $cate = ['cover', 'is_like', 'publish_time'];
        if (!empty($params['post_type']) && $params['post_type'] == PostType::DYNAMIC) {
            $columns = ['post.id', 'post.title', 'post.content', 'post.post_type', 'post.media', 'post.media_type',
                'post.audit_status', 'post.circle_id', 'post.view_count', 'post.comment_count', 'post.like_count',
                'post.user_id', 'user.avatar as user_avatar', 'user.show_icon', 'user.avatar_icon', 'user.nickname', 'post.audit_status', 'post.create_time'];
        } else {
            $params['has_circle'] = true;
            $columns = ['post.id', 'post.title', 'post.content', 'post.post_type', 'post.media', 'post.media_type',
                'post.audit_status', 'post.circle_id', 'circle.name as circle_name', 'post.view_count', 'post.comment_count', 'post.like_count',
                'post.user_id', 'user.avatar as user_avatar', 'user.show_icon', 'user.avatar_icon', 'user.nickname', 'post.audit_status', 'post.create_time'];
        }
        $query = $this->buildQuery($params);
        if ($recommendOrder) {
            $maxValues = $this->getPostRecommendMax();
            $columns[] = Db::raw('(
                (mp_post.view_count / ' . ($maxValues->max_views ?: 1) . ' * 2.5) + 
                (mp_post.comment_count / ' . ($maxValues->max_comments ?: 1) . ' * 2.5) + 
                (mp_post.like_count / ' . ($maxValues->max_likes ?: 1) . ' * 3) + 
                ((1 - ((UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(mp_post.create_time)) / ' . ($maxValues->max_time_diff ?: 1) . ')) * 2)
                ) AS recommendation_score'
            );
            $query->select($columns)
                ->orderBy('recommendation_score', 'desc')
                ->orderBy('post.id', 'desc');
        } else {
            $query = $query->select($columns)
                ->orderBy('post.id', 'desc');
        }
        if ($is_paginate) {
            $page = !empty($params['page']) ? $params['page'] : 1;
            $page_size = !empty($params['page_size']) ? $params['page_size'] : 15;
            $data = $query->paginate((int)$page_size, page: (int)$page);
            $data = paginateTransformer($data);
            if (!empty($data['items'])) {
                foreach ($data['items'] as $key => $item) {
                    $this->objectTransformer($item, $cate, $params);
                }
            }
        } else {
            if ($limit) {
                $data = $query->limit($limit)->get()->toArray();
            } else {
                $data = $query->get()->toArray();
            }
            foreach ($data as $item) {
                $this->objectTransformer($item, $cate, $params);
            }
        }
        return $data;
    }

    public function viewList(int $user_id, array $params = [])
    {
        $query = Db::table('view_history')
            ->leftJoin('post', 'post.id', '=', 'view_history.content_id')
            ->leftJoin('user', 'user.id', '=', 'post.user_id');
        if (!empty($params['post_type']) && $params['post_type'] == PostType::DYNAMIC) {
            $cate = ['cover', 'is_like', 'publish_time'];
            $columns = ['post.id', 'post.title', 'post.content', 'post.post_type', 'post.media', 'post.media_type',
                'post.audit_status', 'post.circle_id', 'post.view_count', 'post.comment_count', 'post.like_count',
                'post.user_id', 'user.avatar as user_avatar', 'user.show_icon', 'user.avatar_icon', 'user.nickname', 'post.audit_status', 'post.create_time'];
        } else {
            $cate = ['publish_time'];
            $query->leftJoin('circle', 'circle.id', '=', 'post.circle_id');
            $columns = ['post.id', 'post.title', 'post.content', 'post.post_type', 'post.media', 'post.media_type',
                'post.audit_status', 'post.circle_id', 'circle.name as circle_name', 'post.view_count', 'post.comment_count', 'post.like_count',
                'post.user_id', 'user.avatar as user_avatar', 'user.show_icon', 'user.avatar_icon', 'user.nickname', 'post.audit_status', 'post.create_time'];
        }
        $page = !empty($params['page']) ? $params['page'] : 1;
        $page_size = !empty($params['page_size']) ? $params['page_size'] : 15;
        $data = $query->where('view_history.user_id', $user_id)
            ->where('view_history.content_type', '=', $params['view_type'])
            ->where('post.audit_status', '=', AuditStatus::PASSED)
            ->where('post.del_flag', '=', 0)
            ->where('post.is_reported', '=', 0)
            ->select($columns)
            ->orderBy('view_history.create_time', 'desc')
            ->paginate((int)$page_size, page: (int)$page);
        $data = paginateTransformer($data);
        foreach ($data['items'] as $item) {
            $this->objectTransformer($item, $cate);
        }
        return $data;
    }

    protected function buildQuery(array $params)
    {
        $query = Db::table('post')
            ->leftJoin('user', 'user.id', '=', 'post.user_id');
        if (!empty($params['has_circle'])) {
            $query->leftJoin('circle', 'circle.id', '=', 'post.circle_id');
        }
        if (!empty($params['is_follow'])) {
            $query->leftJoin('user_follow', 'user_follow.follow_id', '=', 'post.user_id')
                ->where('user_follow.user_id', '=', $params['current_user_id'] ?? 0);
        }
        if (!empty($params['post_id'])) {
            $query->where('post.id', '=', $params['post_id']);
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
        if (isset($params['is_reported'])) {
            $query->where('post.is_reported', '=', 0);
        }
        if (isset($params['audit_del'])) {
            $query->where('post.audit_del', '=', $params['audit_del']);
        }
        if (!isset($params['with_del'])) {
            $query->where('post.del_flag', '=', 0);
        }
        return $query;
    }

    public function getInfo(int $post_id, array $cate = [], int $user_id = 0): object
    {
        $post = Db::table('post')
            ->leftJoin('circle', 'circle.id', '=', 'post.circle_id')
            ->leftJoin('user', 'user.id', '=', 'post.user_id')
            ->where('post.id', '=', $post_id)
            ->select(['post.id', 'post.title', 'post.content', 'post.post_type', 'post.media', 'post.media_type',
                'post.circle_id', 'circle.cover as circle_cover', 'circle.name as circle_name', 'post.view_count', 'post.comment_count', 'post.like_count',
                'post.user_id', 'user.avatar as user_avatar', 'user.show_icon', 'user.avatar_icon', 'user.nickname', 'post.audit_status', 'post.audit_result', 'post.create_time'])
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
            $item->user_info = generalAPiUserInfo($item);
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
        if (in_array('publish_time', $cate)) {
            $item->create_time = getPublishTime(strtotime($item->create_time));
        }
        $color = ['#EEFCFF', '#FFF3E8', '#FFF0EF', '#FCEFFF'];
        $c_id = $item->id % 4;
        $item->bg_color = $color[$c_id];
    }

    public function delete(int $post_id): int
    {
        return Db::table('post')->where('id', $post_id)->update([
            'del_flag' => 1,
            'update_time' => date('Y-m-d H:i:s')
        ]);
    }

    public function auditDelete(int $post_id): int
    {
        return Db::table('post')->where('id', $post_id)->update([
            'audit_del' => 1
        ]);
    }

    public function pass(int $post_id, int $cur_user_id): bool
    {
        $post = Db::table('post')
            ->where('id', '=', $post_id)
            ->first(['id', 'user_id', 'post_type', 'title', 'source', 'audit_status']);
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
            // 帖子发布成功
            $this->publishSuccess($post->user_id, $post->post_type, $post_id);
            // 用户系统消息，给发帖人
            $this->messageService->addSystemMessage(
                $post->user_id,
                MessageCate::POST_PASS->value,
                '你发布的' . ($post->post_type == 2 ? '问答' : '动态') . '：' . $post->title . '，审核通过',
                $post_id,
                ReferType::POST->value,
            );
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
            ->first(['id', 'user_id', 'post_type', 'title', 'source', 'audit_status']);
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
            // 用户系统消息，给发帖人
            $this->messageService->addSystemMessage(
                $post->user_id,
                MessageCate::POST_FAIL->value,
                '你发布的' . ($post->post_type == 2 ? '问答' : '动态') . '：' . $post->title . '，审核被驳回，驳回原因为：' . $reject_reason,
                $post_id,
                ReferType::POST->value,
            );
            Db::commit();
        } catch (\Throwable $ex) {
            Db::rollBack();
            throw new LogicException($ex->getMessage());
        }
        return true;
    }

    public function publish(int $user_id, array $params, $source = 'user'): int
    {
        Db::beginTransaction();
        try {
            $post_id = Db::table('post')->insertGetId([
                'user_id' => $user_id,
                'circle_id' => $params['circle_id'],
                'title' => $params['title'],
                'content' => $params['content'],
                'post_type' => $params['post_type'],
                'media' => empty($params['media']) ? '' : implode(',', $params['media']),
                'media_type' => $params['media_type'] ?? 1,
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s'),
                'source' => $source,
                'audit_status' => $source == 'user' ? AuditStatus::PENDING->value : AuditStatus::PASSED->value,
            ]);
            if ($source == 'user') {
                // 添加审核记录
                $this->auditService->addAuditRecord(AuditType::POST->value, $post_id, $user_id);
            }
            Db::commit();
        } catch (\Throwable $ex) {
            Db::rollBack();
            throw new LogicException($ex->getMessage());
        }
        return $post_id;
    }

    // 帖子发布成功
    protected function publishSuccess(int $user_id, int $post_type, int $post_id)
    {
        // 金币奖励
        $this->creditService->finishCoinTask($user_id, CoinCate::POST, $post_id, '发布帖子');
        // 声望奖励
        if ($post_type == PostType::DYNAMIC->value) {
            $this->creditService->finishPrestigeTask($user_id, PrestigeCate::DYNAMIC, $post_id, '发布动态');
        } else {
            $this->creditService->finishPrestigeTask($user_id, PrestigeCate::QA, $post_id, '发布问答');
        }
    }

    public function like(int $post_id, int $user_id, int $status): bool
    {
        $has = Db::table('post_like')
            ->where(['post_id' => $post_id, 'user_id' => $user_id])
            ->count();
        if ((empty($has) && $status == 0) || (!empty($has) && $status == 1)) {
            return true;
        }
        $post = Db::table('post')
            ->where('id', '=', $post_id)
            ->first(['id', 'source', 'audit_status', 'user_id']);
        if (empty($post)) {
            throw new LogicException('帖子不存在');
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
                $this->likeSuccess($user_id, $post);
            } else {
                $res1 = Db::table('post_like')
                    ->where(['post_id' => $post_id, 'user_id' => $user_id])
                    ->delete();
                $res2 = Db::table('post')->where('id', '=', $post_id)->decrement('like_count');
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
    protected function likeSuccess(int $user_id, object $post)
    {
        // 声望
        $this->creditService->finishPrestigeTask($post->user_id, PrestigeCate::BE_LIKED, $post->id, '获赞', ReferType::POST->value, $user_id);
        $this->creditService->finishPrestigeTask($user_id, PrestigeCate::LIKE, $post->id, '点赞', ReferType::POST->value);
        // 用户消息
        $this->messageService->addLikeMessage($post->user_id, MessageCate::POST_LIKE->value, ReferType::POST->value, $post->id, $user_id);
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

    public function share(int $user_id, int $post_id)
    {
        Db::beginTransaction();
        try {
            Db::table('post_share')->insert([
                'user_id' => $user_id,
                'post_id' => $post_id,
                'create_time' => date('Y-m-d H:i:s'),
            ]);
            $this->creditService->finishPrestigeTask($user_id, PrestigeCate::SHARE, $post_id, '帖子分享', ReferType::POST->value);
            Db::commit();
        } catch (\Throwable $ex) {
            Db::rollBack();
            throw new ParametersException($ex->getMessage());
        }
        return true;
    }


    // 当天发帖统计；按小时统计
    public function dailyPublishStatics(string $date = '')
    {
        $date = $date ?: date('Y-m-d');
        $startTime = Carbon::parse($date)->startOfDay()->format('Y-m-d H:i:s');
        $endTime = Carbon::parse($date)->endOfDay()->format('Y-m-d H:i:s');
        $query = Db::table('post')->where('del_flag', 0);
        $query->whereBetween('create_time', [$startTime, $endTime]);
        $hourlyData = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $timeLabel = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
            $hourlyData[$timeLabel] = 0;
        }
        $results = $query->selectRaw('DATE_FORMAT(create_time, "%H:00") as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->get()
            ->toArray();
        $results = array_column($results, 'count', 'hour');
        $list = [];
        foreach ($hourlyData as $hour => $count) {
            $list[] = [
                'time' => $hour,
                'count' => $results[$hour] ?? 0
            ];
        }
        return $list;
    }

    // N天发帖统计，按天统计
    public function daysPublishStatics(int $days)
    {
        $endTime = Carbon::yesterday()->endOfDay()->format('Y-m-d H:i:s');
        $startTime = Carbon::now()->subDays($days)->startOfDay()->format('Y-m-d H:i:s');
        $query = Db::table('post')->where('del_flag', 0);
        $query->whereBetween('create_time', [$startTime, $endTime]);
        $dailyData = [];
        for ($i = $days; $i >= 1; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dailyData[$date] = 0;
        }
        $results = $query->selectRaw('DATE(create_time) as date, COUNT(*) as count')
            ->groupBy('date')
            ->get()
            ->toArray();
        $results = array_column($results, 'count', 'date');
        $list = [];
        foreach ($dailyData as $date => $count) {
            $list[] = [
                'time' => $date,
                'count' => $results[$date] ?? 0
            ];
        }
        return $list;
    }
}