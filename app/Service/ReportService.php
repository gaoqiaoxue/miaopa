<?php

namespace App\Service;

use App\Constants\AuditStatus;
use App\Constants\AuditType;
use App\Constants\PrestigeCate;
use App\Constants\ReferType;
use App\Constants\ReportReason;
use App\Constants\ReportType;
use App\Exception\LogicException;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class ReportService
{
    #[Inject]
    protected AuditService $auditService;

    #[Inject]
    protected PostsService $postsService;

    #[Inject]
    protected CommentService $commentService;

    #[Inject]
    protected CreditService $creditService;

    public function getAuditList(array $params = []): array
    {
        $query = Db::table('report')
            ->leftJoin('user', 'user.id', '=', 'report.user_id');
        if (!empty($params['user_id'])) {
            $query->where('report.user_id', '=', $params['user_id']);
        }
        if (!empty($params['nickname'])) {
            $query->where('user.nickname', 'like', '%' . $params['nickname'] . '%');
        }
        if (!empty($params['report_type'])) {
            $query->where('report.report_type', '=', $params['report_type']);
        }
        if (!empty($params['report_reason'])) {
            $query->where('report.report_reason', '=', $params['report_reason']);
        }
        if (isset($params['audit_status']) && in_array($params['audit_stauts'], AuditStatus::getKeys())) {
            $query->where('report.audit_status', '=', $params['audit_status']);
        }
        if (!empty($params['start_time']) && !empty($params['end_time'])) {
            $query->whereBetween('report.create_time', [$params['start_time'], $params['end_time']]);
        }

        $page = !empty($params['page']) ? $params['page'] : 1;
        $page_size = !empty($params['page_size']) ? $params['page_size'] : 15;
        $data = $query->select(['report.id', 'report.report_type', 'report.report_reason', 'report.description',
            'report.audit_status', 'report.audit_result', 'report.create_time', 'user.nickname'])
            ->orderBy('report.create_time', 'desc')
            ->paginate((int)$page_size, page: (int)$page);
        $data = paginateTransformer($data);
        return $data;
    }

    public function getInfo(int $report_id): object
    {
        $info = Db::table('report')
            ->where('id', '=', $report_id)
            ->select(['id', 'user_id', 'content_id', 'content_user_id', 'report_type', 'report_reason', 'description', 'images', 'audit_status', 'audit_result', 'create_time'])
            ->first();
        if (!$info) {
            throw new LogicException('举报信息不存在');
        }
        $info->report_reason = ReportReason::from($info->report_reason)->getMessage();
        $info->images = generateMulFileUrl($info->images);
        $content = null;
        if ($info->report_type == ReportType::POST->value) {
            $content = $this->postsService->getInfo($info->content_id);
        } elseif ($info->report_type == ReportType::COMMENT->value) {
            $content = $this->commentService->getInfo($info->content_id);
            $content->post = $this->postsService->getInfo($info->post_id);
        }
        $info->content = $content;
        return $info;

    }

    public function pass(int $report_id, int $cur_user_id, int $mute_time): bool
    {
        $report = Db::table('report')
            ->where('id', '=', $report_id)
            ->first(['id', 'report_type', 'content_id', 'content_user_id', 'audit_status']);
        if ($report->audit_status != AuditStatus::PENDING->value) {
            throw new LogicException('该举报信息已经审核过了');
        }
        $audit_result = empty($mute_time) ? '' : '禁言' . $mute_time . '分钟';
        Db::beginTransaction();
        try {
            Db::table('report')->where('id', '=', $report_id)->update([
                'audit_status' => AuditStatus::PASSED->value,
                'audit_result' => $audit_result,
            ]);
            $this->auditService->pass(AuditType::REPORT->value, $report_id, $cur_user_id, $audit_result);
            if (!empty($mute_time)) {
                Db::table('user')->where('id', '=', $report->content_user_id)->update([
                    'mute_time' => time() + $mute_time * 60,
                ]);
            }
            if ($report->report_type == ReportType::POST->value) {
                Db::table('post')->where('id', '=', $report->content_id)->update([
                    'is_reported' => 1,
                ]);
            } elseif ($report->report_type == ReportType::COMMENT->value) {
                Db::table('comment')->where('id', '=', $report->content_id)->update([
                    'is_reported' => 1,
                ]);
            }
            $this->reportSuccess((array) $report);
            Db::commit();
        } catch (\Throwable $ex) {
            Db::rollBack();
            throw new LogicException($ex->getMessage());
        }
        return true;
    }

    public function reject(int $report_id, int $cur_user_id, string $reject_reason): bool
    {
        $report = Db::table('report')
            ->where('id', '=', $report_id)
            ->first(['id', 'report_type', 'content_id', 'audit_status']);
        if ($report->audit_status != AuditStatus::PENDING->value) {
            throw new LogicException('该举报信息已经审核过了');
        }
        Db::beginTransaction();
        try {
            Db::table('report')->where('id', '=', $report_id)->update([
                'audit_status' => AuditStatus::REJECTED->value,
                'audit_result' => $reject_reason,
            ]);
            $this->auditService->reject(AuditType::REPORT->value, $report_id, $cur_user_id, $reject_reason);
            Db::commit();
        } catch (\Throwable $ex) {
            Db::rollBack();
            throw new LogicException($ex->getMessage());
        }
        return true;
    }

    public function report(int $user_id, ReportType $report_type, array $params):int
    {
        if($report_type == ReportType::POST){
            $content = Db::table('post')->where(['id'=>$params['post_id']])->first(['id, user_id']);
        }elseif ($report_type == ReportType::COMMENT){
            $content = Db::table('comment')->where(['id'=>$params['comment_id']])->first(['id, user_id']);
        }else{
            throw new LogicException('举报类型错误');
        }
        if(empty($content)){
            throw new LogicException('举报内容不存在');
        }
        Db::beginTransaction();
        $report_id = Db::table('report')->insertGetId([
            'user_id' => $user_id,
            'report_type' => $report_type,
            'content_id' => $params['content_id'],
            'content_user_id' => $content->user_id,
            'report_reason' => $params['report_reason'],
            'description' => $params['description'] ?? '',
            'images' => empty($params['images']) ? '' : implode(',', $params['images']),
            'audit_status' => AuditStatus::PENDING->value,
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ]);
        $this->auditService->addAuditRecord(AuditType::REPORT->value, $report_id, $user_id);
        Db::commit();
        return $report_id;
    }

    protected function reportSuccess(array $report)
    {
        $refer_type = match ($report['report_type']){
            ReportType::POST->value => ReferType::POST->value,
            ReportType::COMMENT->value => ReferType::COMMENT->value,
            default => throw new LogicException('举报类型错误')
        };
        // 声望
        $this->creditService->finishPrestigeTask($report['user_id'], PrestigeCate::REPORT, $report['id'], '举报成功', $refer_type);
    }

}