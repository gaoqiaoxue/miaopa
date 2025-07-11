<?php

namespace App\Service;

use App\Constants\PostType;
use App\Exception\LogicException;
use Hyperf\DbConnection\Db;

class UserViewRecordService
{
    public function addPostViewRecord(int $post_type, int $user_id, int $post_id)
    {
        // TODO 检验重复记录
        $type = match ($post_type){
            PostType::DYNAMIC->value => 'dynamic',
            PostType::QA->value => 'qa',
            default => '',
        };
        Db::beginTransaction();
        try {
            Db::table('post')->where('id', $post_id)->increment('view_count');

            Db::table('view_history')->insert([
                'user_id' => $user_id,
                'content_type' => $type,
                'content_id' => $post_id,
                'create_time' => date('Y-m-d H:i:s'),
            ]);
            Db::commit();
        } catch (\Throwable $ex) {
            Db::rollBack();
            throw new LogicException($ex->getMessage());
        }
        return true;
    }

    public function addActivityViewRecord(int $user_id, int $activity_id)
    {
        // TODO 检验重复记录
        Db::beginTransaction();
        try {
            Db::table('view_history')->insert([
                'user_id' => $user_id,
                'content_type' => 'activity',
                'content_id' => $activity_id,
                'create_time' => date('Y-m-d H:i:s'),
            ]);
            Db::commit();
        } catch (\Throwable $ex) {
            Db::rollBack();
            throw new LogicException($ex->getMessage());
        }
        return true;
    }

}