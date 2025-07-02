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
        'pass' => ['report_id', 'mute_time']
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
        ];

    }
}
