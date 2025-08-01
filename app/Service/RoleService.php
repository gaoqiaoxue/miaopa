<?php

namespace App\Service;

use App\Constants\AuditStatus;
use App\Constants\AuditType;
use App\Constants\CircleRelationType;
use App\Constants\MessageCate;
use App\Constants\ReferType;
use App\Constants\RoleType;
use App\Exception\LogicException;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class RoleService
{
    #[Inject]
    protected AuditService $auditService;

    #[Inject]
    protected MessageService $messageService;

    public function getList(array $params): array
    {
        $query = Db::table('role');
        if (!empty($params['name'])) {
            $query->where('name', 'like', '%' . $params['name'] . '%');
        }
        if (!empty($params['role_type'])) {
            $query->where('role_type', '=', $params['role_type']);
        }
        if (isset($params['status']) && in_array($params['status'], [0, 1])) {
            $query->where('status', '=', $params['status']);
        }
        if (isset($params['audit_status']) && in_array($params['audit_status'], AuditStatus::getKeys())) {
            $query->where('audit_status', '=', $params['audit_status']);
        }
        if (!empty($params['source'])) {
            $query->where('source', '=', $params['source']);
        }
        if (!empty($params['circle_id'])) {
            $query->where('circle_id', '=', $params['circle_id']);
        }
        if (!empty($params['author'])) {
            $query->where('author', 'like', '%' . $params['author'] . '%');
        }
        if (!empty($params['start_time']) && !empty($params['end_time'])) {
            $query->whereBetween('create_time', [$params['start_time'], $params['end_time']]);
        }
        $page = !empty($params['page']) ? $params['page'] : 1;
        $page_size = !empty($params['page_size']) ? $params['page_size'] : 15;
        $list = $query->select(['id', 'name', 'alias', 'cover', 'role_type', 'author', 'circle_id', 'weight',
            'images', 'description', 'status', 'audit_status', 'source', 'create_by', 'create_time'])
            ->orderBy('id', 'desc')
            ->paginate((int)$page_size, page: (int)$page);
        $data = paginateTransformer($list);
        if (!empty($data['items'])) {
            $circle_ids = array_column($data['items'], 'circle_id');
            $cirlces = Db::table('circle')
                ->whereIn('id', $circle_ids)
                ->pluck('name', 'id')
                ->toArray();
            $role_types = RoleType::getMaps();
            foreach ($data['items'] as $item) {
                $item->role_type_name = $role_types[$item->role_type] ?? '';
                $item->cover_url = generateFileUrl($item->cover);
                $item->circle_name = $cirlces[$item->circle_id] ?? '';
            }
        }
        return $data;
    }

    public function getInfo(int $role_id, array $cate = ['circle', 'create_by']): object
    {
        $info = Db::table('role')
            ->where(['id' => $role_id])
            ->select(['id', 'name', 'alias', 'cover', 'role_type', 'author', 'circle_id', 'weight',
                'images', 'description', 'status', 'audit_status', 'audit_result', 'source', 'create_at', 'create_by', 'create_time'])
            ->first();
        if (!$info) {
            throw new LogicException('角色不存在');
        }
        return $this->objectTransformer($info, $cate);
    }

    protected function objectTransformer(object $info, array $cate = [], array $extra = []): object
    {
        if (property_exists($info, 'cover')) {
            $info->cover_url = generateFileUrl($info->cover);
        }
        if (property_exists($info, 'images')) {
            $info->images = generateMulFileUrl($info->images);
        }
        if (property_exists($info, 'role_type')) {
            $info->role_type_text = RoleType::from($info->role_type)->getMessage();
        }
        if (in_array('circle', $cate)) {
            if (isset($extra['circles'])) {
                $info->circle_name = $extra['circles'][$info->circle_id] ?? '';
            } else {
                $info->circle_name = Db::table('circle')
                    ->where('id', '=', $info->circle_id)
                    ->value('name');
            }
        }
        if (in_array('create_by', $cate)) {
            if ($info->source == 'admin') {
                $info->creater_name = Db::table('sys_user')
                    ->where('user_id', '=', $info->create_by)
                    ->value('nick_name');
            } else {
                $info->creater_name = Db::table('user')
                    ->where('id', '=', $info->create_by)
                    ->value('nickname');
            }
        }
        return $info;
    }

    public function add(array $params, $create_by = 0, $source = 'admin'): int
    {
        $data = $this->generalData($params, true, $create_by, $source);
        Db::beginTransaction();
        try {
            $role_id = Db::table('role')->insertGetId($data);
            if ($source == 'user') {
                $this->auditService->addAuditRecord(AuditType::ROLE->value, $role_id, $create_by);
            }
            if(!empty($data['circle_id'])){
                $this->updateCircleRoleRelation($data['circle_id'], $role_id);
            }
            Db::commit();
        } catch (\Throwable $ex) {
            Db::rollBack();
            throw new LogicException($ex->getMessage());
        }
        return $role_id;
    }

    public function edit(array $params): int
    {
        $data = $this->generalData($params);
        $role_id = $params['role_id'];
        return Db::table('role')->where('id', '=', $role_id)->update($data);
    }

    protected function generalData(array $params, bool $is_add = false, $create_by = 0, $source = 'admin'): array
    {
        $cover = !empty($params['cover']) ? $params['cover'] : ($params['images'][0] ?? 0);
        $data = [
            'name' => $params['name'],
            'alias' => $params['alias'] ?? '',
            'cover' => $cover,
            'role_type' => $params['role_type'] ?? 0,
            'author' => $params['author'] ?? '',
            'circle_id' => $params['circle_id'] ?? 0,
            'weight' => $params['weight'] ?? 100,
            'description' => $params['description'] ?? '',
            'images' => implode(',', empty($params['images']) ? [] : $params['images']),
            'status' => $params['status'] ?? 1,
            'update_time' => date('Y-m-d H:i:s'),
        ];
        if ($is_add) {
            $data['create_at'] = $params['create_at'] ?? date('Y-m-d');
            $data['create_time'] = date('Y-m-d H:i:s');
            $data['create_by'] = $create_by;
            $data['source'] = $source;
            if ($source == 'admin') {
                $data['audit_status'] = AuditStatus::PASSED;
            } else {
                $data['audit_status'] = AuditStatus::PENDING;
            }
        }
        return $data;
    }

    public function changeStatus(int $role_id, int $status): int
    {
        return Db::table('role')->where('id', '=', $role_id)->update(['status' => $status]);
    }

    public function pass(int $role_id, int $cur_user_id): bool
    {
        $role = Db::table('role')
            ->where('id', '=', $role_id)
            ->first(['id', 'circle_id', 'name','source', 'create_by', 'audit_status']);
        if ($role->audit_status != AuditStatus::PENDING->value) {
            throw new LogicException('该角色已经审核过了');
        }
        Db::beginTransaction();
        try {
            Db::table('role')->where('id', '=', $role_id)->update([
                'audit_status' => AuditStatus::PASSED->value,
                'update_time' => date('Y-m-d H:i:s'),
            ]);
            $this->auditService->pass(AuditType::ROLE->value, $role_id, $cur_user_id);
            // 用户系统消息，给评论人
            if($role->source == 'user'){
                $this->messageService->addSystemMessage(
                    $role->user_id,
                    MessageCate::ROLE_PASS->value,
                    '您发布的角色《' . $role->name . '》审核通过',
                    $role_id,
                    ReferType::ROLE->value,
                );
            }
            Db::commit();
        } catch (\Throwable $ex) {
            Db::rollBack();
            throw new LogicException($ex->getMessage());
        }
        return true;
    }

    public function reject(int $role_id, int $cur_user_id, string $reject_reason)
    {
        $role = Db::table('role')
            ->where('id', '=', $role_id)
            ->first(['id', 'circle_id', 'name','source', 'create_by', 'audit_status']);
        if ($role->audit_status != AuditStatus::PENDING->value) {
            throw new LogicException('该角色已经审核过了');
        }
        Db::beginTransaction();
        try {
            Db::table('role')->where('id', '=', $role_id)->update([
                'audit_status' => AuditStatus::REJECTED->value,
                'audit_result' => $reject_reason,
            ]);
            $this->auditService->reject(AuditType::ROLE->value, $role_id, $cur_user_id, $reject_reason);
            $this->updateCircleRoleRelation($role->circle_id, $role_id, false);
            // 用户系统消息，给评论人
            if($role->source == 'user'){
                $this->messageService->addSystemMessage(
                    $role->user_id,
                    MessageCate::ROLE_FAIL->value,
                    '您发布的角色《' . $role->name . '》审核被驳回，驳回原因为：' . $reject_reason,
                    $role_id,
                    ReferType::ROLE->value,
                );
            }
            Db::commit();
        } catch (\Throwable $ex) {
            Db::rollBack();
            throw new LogicException($ex->getMessage());
        }
        return true;
    }

    private function updateCircleRoleRelation(int $circle_id, int $role_id, bool $is_add = true)
    {
        $circle = Db::table('circle')
            ->where('id', '=', $circle_id)
            ->first(['id', 'name', 'relation_type', 'relation_ids']);
        if($circle->relation_type != CircleRelationType::ROLE->value){
            throw new LogicException('该圈子关联类型不是角色');
        }
        $relation_ids = json_decode($circle->relation_ids,true);
        if($is_add && !in_array($role_id,$relation_ids)){
            $relation_ids[] = $role_id;
            if(count($relation_ids) > 100){
                throw new LogicException('圈子'.$circle->name.'关联角色数量已经超过100个');
            }
            Db::table('circle')->where('id', '=', $circle_id)->update([
                'relation_ids' => json_encode($relation_ids),
            ]);
        }elseif (!$is_add && in_array($role_id,$relation_ids)){
            $relation_ids = array_diff($relation_ids, [$role_id]);
            $relation_ids = array_values($relation_ids);
            Db::table('circle')->where('id', '=', $circle_id)->update([
                'relation_ids' => json_encode($relation_ids),
            ]);
        }
        return true;
    }
}