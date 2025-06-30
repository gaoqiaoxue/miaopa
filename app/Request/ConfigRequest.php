<?php

declare(strict_types=1);

namespace App\Request;

use Hyperf\Validation\Request\FormRequest;

class ConfigRequest extends FormRequest
{
    protected array $scenes = [
        'publish' => ['post_publish_type', 'comment_publish_type', 'report_publish_type'],
        'coins' => ['daily_sign_coins', 'continuous_sign_config', 'post_coins', 'comment_coins', 'activity_coins', 'stay_time_config'],
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
            'post_publish_type' => 'required|in:1,2',
            'comment_publish_type' => 'required|in:1,2',
            'report_publish_type' => 'required|in:1,2',
            'daily_sign_coins' => 'required|integer',
            'continuous_sign_config' => 'array',
            'post_coins' => 'required|integer',
            'comment_coins' => 'required|integer',
            'activity_coins' => 'required|integer',
            'stay_time_config' => 'array',
        ];
    }


    public function messages(): array
    {
        return [
            'post_publish_type.required' => '帖子发布配置不能为空',
            'post_publish_type.in' => '帖子发布配置必须为1或2',
            'comment_publish_type.required' => '评论发布配置不能为空',
            'comment_publish_type.in' => '评论发布配置必须为1或2',
            'report_publish_type.required' => '举报审核配置不能为空',
            'report_publish_type.in' => '举报审核配置必须为1或2',
            'daily_sign_coins.required' => '每日签到领取币数量不能为空',
            'daily_sign_coins.integer' => '每日签到领取币数量必须为整数',
            'post_coins.required' => '发布动态领取币数量不能为空',
            'post_coins.integer' => '发布动态领取币数量必须为整数',
            'comment_coins.required' => '参与讨论领取币数量不能为空',
            'comment_coins.integer' => '参与讨论领取币数量必须为整数',
            'activity_coins.required' => '报名一次漫展领取币数量不能为空',
            'activity_coins.integer' => '报名一次漫展领取币数量必须为整数',
            'stay_time_config.array' => '停留时间配置必须为数组',
        ];
    }

}