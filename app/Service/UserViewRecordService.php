<?php

namespace App\Service;

use App\Constants\PostType;
use App\Exception\LogicException;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\DbConnection\Db;

class UserViewRecordService
{
    #[Cacheable(prefix: 'view_history', ttl: 3600)]
    public function addViewRecord(string $type, int $user_id, int $content_id)
    {
        Db::beginTransaction();
        try {
            if ($type == 'dynamic' || $type == 'qa') {
                Db::table('post')->where('id', $content_id)->increment('view_count');
            }
            Db::table('view_history')->insert([
                'user_id' => $user_id,
                'content_type' => $type,
                'content_id' => $content_id,
                'create_time' => date('Y-m-d H:i:s'),
            ]);
            Db::commit();
        } catch (\Throwable $ex) {
            Db::rollBack();
            throw new LogicException($ex->getMessage());
        }
        return true;
    }

    public function getPostViewType(int $post_type): string
    {
        return match ($post_type) {
            PostType::DYNAMIC->value => 'dynamic',
            PostType::QA->value => 'qa',
            default => '',
        };
    }

}