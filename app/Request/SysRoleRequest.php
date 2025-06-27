<?php

declare(strict_types=1);

namespace App\Request;

use Hyperf\Validation\Request\FormRequest;

class SysRoleRequest extends FormRequest
{
    protected array $scenes = [
        'add' => ['role_name', 'remark', 'menu_ids'],
        'edit' => ['role_id', 'role_name', 'remark'],
        'changeStatus' => ['role_id', 'status'],
        'changePerms' => ['role_id', 'menu_ids'],
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'role_id' => 'required',
            'role_name' => 'required',
            'remark' => 'required',
            'menu_ids' => 'required|array',
            'status' => 'required|in:0,1',
        ];
    }


    public function messages(): array
    {
        return [
            'role_id.required' => '角色ID不能为空',
            'role_name.required' => '请填写角色名称',
            'remark.required' => '请填写备注',
            'menu_ids.required' => '请填写权限',
            'menu_ids.array' => '权限格式错误',
            'status.required' => '请填写状态',
            'status.in' => '状态错误',
        ];
    }
}
