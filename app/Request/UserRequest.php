<?php

declare(strict_types=1);

namespace App\Request;

use Hyperf\Validation\Request\FormRequest;

class UserRequest extends FormRequest
{
    protected array $scenes = [
        'login' => ['username', 'password'],
        'wechat_auth' => ['code'],
        'wechat_bind' => ['code', 'core_id']
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
            'username' => 'required',
            'password' => 'required|alpha_dash:ascii|between:6,20',
            'code' => 'required',
            'core_id' => 'required|integer',
        ];
    }


    public function messages(): array
    {
        return [
            'username.required' => '请填写账号',
            'password.required' => '请填写密码',
            'password.alpha_dash' => '密码应由字数字母下划线组成',
            'password.between' => '密码长度应在6-20字符',
        ];

    }
}
