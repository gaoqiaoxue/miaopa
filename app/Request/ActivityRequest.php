<?php

declare(strict_types=1);

namespace App\Request;

use App\Constants\CircleType;
use Hyperf\Validation\Request\FormRequest;

/**
 * 活动
 */
class ActivityRequest extends FormRequest
{
    protected array $scenes = [
        'add' => ['bg', 'cover', 'name', 'activity_type', 'organizer', 'is_hot', 'weight', 'status', 'city_id', 'address', 'lat', 'lon', 'fee', 'start_date', 'end_date', 'start_time', 'end_time', 'tags', 'details'],
        'edit' => ['activity_id', 'bg', 'cover', 'name', 'activity_type', 'organizer', 'is_hot', 'weight', 'status', 'city_id', 'address', 'lat', 'lon', 'fee', 'start_date', 'end_date', 'start_time', 'end_time', 'tags', 'details'],
        'change_status' => ['activity_id', 'status'],
        'id' => ['activity_id'],
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
            'bg' => 'required|array',
            'cover' => 'required|string',
            'name' => 'required',
            'activity_type' => 'required|in:1',
            'organizer' => 'required',
            'is_hot' => 'required|in:0,1',
            'weight' => 'integer',
            'status' => 'required|in:0,1',
            'city_id' => 'required',
            'address' => 'required',
            'lat' => 'numeric|between:-90,90',
            'lon' => 'numeric|between:-180,180',
            'fee' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
            'start_time' => 'required',
            'end_time' => 'required',
            'tags' => 'array',
            'details' => 'required',
            'activity_id' => 'required|integer',
        ];
    }


    public function messages(): array
    {
        return [
            'bg.required' => '请上传活动背景图',
            'cover.required' => '请上传活动封面图',
            'name.required' => '请输入活动名称',
            'activity_type.required' => '请选择活动类型',
            'activity_type.in' => '活动类型错误',
            'is_hot.required' => '请选择是否为热门活动',
            'is_hot.in' => 'is_hot仅支持0,1',
            'weight.integer' => '权重必须为整数',
            'status.required' => '请选择状态',
            'status.in' => '状态错误',
            'city_id.required' => '请选择城市',
            'address.required' => '请输入地址',
            'fee.required' => '请输入费用',
            'start_date.required' => '请选择开始日期',
            'end_date.required' => '请选择结束日期',
            'start_time.required' => '请选择开始时间',
            'end_time.required' => '请选择结束时间',
            'tags.array' => '标签必须为数组',
            'details.required' => '请输入活动详情',
            'activity_id.required' => '请输入活动id',
            'activity_id.integer' => '活动id必须为整数',
        ];

    }
}
