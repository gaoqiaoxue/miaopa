<?php

declare(strict_types=1);

namespace App\Request;

use App\Constants\CircleType;
use Hyperf\Validation\Request\FormRequest;

/**
 * 圈子
 */
class CircleRequest extends FormRequest
{
    protected array $scenes = [
        'add' => ['bg', 'cover', 'name', 'circle_type', 'is_hot', 'weight', 'status', 'relation_type', 'relation_ids', 'description'],
        'edit' => ['circle_id', 'bg', 'cover', 'name', 'circle_type', 'is_hot', 'weight', 'status', 'relation_type', 'relation_ids', 'description'],
        'change_status' => ['circle_id', 'status'],
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
            'bg' => 'required',
            'cover' => 'required',
            'name' => 'required',
            'circle_type' => 'required|in:1,2,3',
            'is_hot' => 'required|in:0,1',
            'weight' => 'integer',
            'status' => 'required|in:0,1',
            'relation_type' => 'in:circle,role',
            'relation_ids' => 'array',
            'description' => 'required',
            'circle_id' => 'required|integer',
        ];
    }


    public function messages(): array
    {
        return [
            'bg.required' => '请上传圈子背景图',
            'cover.required' => '请上传圈子封面图',
            'name.required' => '请输入圈子名称',
            'circle_type.required' => '请选择圈子类型',
            'circle_type.in' => '圈子类型错误',
            'is_hot.required' => '请选择是否为热门圈子',
            'is_hot.in' => '热门圈子错误',
            'weight.required' => '请输入权重',
            'weight.integer' => '权重必须为整数',
            'status.required' => '请选择状态',
            'status.in' => '状态错误',
            'relation_type.required' => '请选择关联类型',
            'relation_type.in' => '关联类型错误',
            'relation_ids.array' => '关联id必须为数组',
            'description.required' => '请输入圈子简介',
            'circle_id.required' => '请输入圈子id',
            'circle_id.integer' => '圈子id必须为整数',
        ];

    }
}
