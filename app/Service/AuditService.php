<?php

namespace App\Service;

use App\Constants\AuditStatus;
use App\Exception\LogicException;
use Hyperf\DbConnection\Db;

class AuditService
{
    public function addAuditRecord(int $audit_type, int $content_id, int $user_id)
    {
        return Db::table('audit_record')->insert([
            'audit_type' => $audit_type,
            'content_id' => $content_id,
            'user_id' => $user_id,
            'status' => AuditStatus::PENDING,
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ]);
    }

    public function pass(int $audit_type, int $content_id, int $handler_id, string $result = ''):bool
    {
        $res = Db::table('audit_record')
            ->where('audit_type', $audit_type)
            ->where('content_id', $content_id)
            ->where('status', AuditStatus::PENDING->value)
            ->update([
                'status' => AuditStatus::PASSED->value,
                'result' => $result,
                'handler_id' => $handler_id,
                'handle_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s'),
            ]);
        if(!$res){
            throw  new LogicException('审核记录更新失败');
        }
        return true;
    }

    public function reject(int $audit_type, int $content_id, int $handler_id, string $reject_reason = '')
    {
        $res = Db::table('audit_record')
            ->where('audit_type', $audit_type)
            ->where('content_id', $content_id)
            ->where('status', AuditStatus::PENDING->value)
            ->update([
                'status' => AuditStatus::REJECTED->value,
                'result' => $reject_reason,
                'handler_id' => $handler_id,
                'handle_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s'),
            ]);
        if(!$res){
            throw  new LogicException('审核记录更新失败');
        }
        return true;
    }
}