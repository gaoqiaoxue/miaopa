<?php

declare(strict_types=1);

namespace App\Request;

use Hyperf\Validation\Request\FormRequest;

class RoleRequest extends FormRequest
{
    protected array $scenes = [
        'id' => ['role_id'],
        'user_add' => ['name', 'alias', 'role_type', 'author', 'circle_id', 'description', 'images', 'create_at'],
        'add' => ['name', 'alias', 'cover', 'role_type', 'author', 'circle_id', 'weight', 'description', 'images', 'status'],
        'edit' => ['role_id', 'name', 'alias', 'cover', 'role_type', 'author', 'circle_id', 'weight', 'description', 'images', 'status'],
        'change_status' => ['role_id', 'status'],

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
            'role_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'alias' => 'string|max:255',
            'cover' => 'string',
            'role_type' => 'integer|in:1,2',
            'author' => 'string|max:255',
            'circle_id' => 'integer',
            'weight' => 'integer',
            'description' => 'string',
            'images' => 'array',
            'status' => 'integer|in:0,1',
            'create_at' => 'date',
        ];
    }


    public function messages(): array
    {
        return [
            'role_id.required' => '角色ID不能为空',
            'role_id.integer' => '角色ID必须为整数',
            'name.required' => '角色名称不能为空',
            'name.string' => '角色名称必须为字符串',
            'name.max' => '角色名称最多不能超过255个字符',
            'alias.string' => '角色别名必须为字符串',
            'alias.max' => '角色别名最多不能超过255个字符',
            'cover.string' => '封面图必须为字符串',
            'role_type.integer' => '角色分类必须为整数',
            'role_type.in' => '角色分类必须为1或2',
            'author.string' => '作者必须为字符串',
            'author.max' => '作者最多不能超过255个字符',
            'circle_id.required' => '关联圈子ID不能为空',
            'circle_id.integer' => '关联圈子ID必须为整数',
            'weight.integer' => '权重必须为整数',
            'description.string' => '简介必须为字符串',
            'images.array' => '图片库必须为数组',
            'status.integer' => '状态必须为整数',
            'status.in' => '状态必须为0或1',
        ];
    }
}
