<?php

declare(strict_types=1);

namespace App\Request;

use Hyperf\Validation\Request\FormRequest;

class
ReportRequest extends FormRequest
{
    protected array $scenes = [
        'id' => ['report_id'],
        'reject' => ['report_id','reject_reason'],
        'pass' => ['report_id', 'mute_time'],
        'post_report' => ['post_id', 'report_reason', 'description', 'images'],
        'comment_report' => ['comment_id', 'report_reason', 'description', 'images']
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
            'report_id' => 'required|integer',
            'mute_time' => 'integer',
            'report_reason' => 'required|integer',
            'description' => 'string',
            'images' => 'array',
            'post_id' => 'required|integer',
            'comment_id' => 'required|integer',
            'reject_reason' => 'required|string',
        ];
    }


    public function messages(): array
    {
        return [
            'report_id.required' => '举报ID不能为空',
            'report_id.integer' => '举报ID必须为整数',
            'mute_time.integer' => '禁言时间必须为整数',
            'reject_reason.required' => '拒绝理由不能为空',
            'reject_reason.string' => '拒绝理由必须为字符串',
            'report_reason.required' => '举报原因不能为空',
            'report_reason.integer' => '举报原因必须为整数',
            'description.required' => '举报描述不能为空',
            'description.string' => '举报描述必须为字符串',
            'images.array' => '图片必须为数组',
            'post_id.required' => '帖子ID不能为空',
            'post_id.integer' => '帖子ID必须为整数',
            'comment_id.required' => '评论ID不能为空',
            'comment_id.integer' => '评论ID必须为整数',
        ];

    }
}
