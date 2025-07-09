<?php

declare(strict_types=1);

namespace App\Request;

use App\Constants\CircleType;
use Hyperf\Validation\Request\FormRequest;

/**
 * 用户关注
 */
class UserFollowRequest extends FormRequest
{
    protected array $scenes = [
        'follow' => ['follow_id', 'status']
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
            'follow_id' => 'required|integer',
            'status' => 'required|in:0,1',
        ];
    }


    public function messages(): array
    {
        return [
            'follow_id.required' => '缺少必要参数follow_id',
            'follow_id.integer' => '参数follow_id必须为整数',
            'status.required' => '缺少必要参数status',
            'status.in' => '参数status必须为0或1',
        ];

    }
}
