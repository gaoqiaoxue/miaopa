<?php

declare(strict_types=1);

namespace App\Request;

use Hyperf\Validation\Request\FormRequest;

class SysUserRequest extends FormRequest
{
    protected array $scenes = [
        'login' => ['user_name', 'password'],
        'add' => ['user_name', 'nick_name', 'phonenumber', 'password', 'role_id'],
        'edit' => ['user_id', 'user_name', 'nick_name', 'phonenumber', 'role_id'],
        'change_status' => ['user_id', 'status'],
        'change_psw' => ['user_id', 'password'],
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
            'user_name' => 'required',
            'password' => 'required|alpha_dash:ascii|between:6,20',
            'nick_name' => 'required',
            'user_id' => 'required',
            'role_id' => 'required',
            'phonenumber' => 'required|regex:/^1[3-9]\d{9}$/',
            'status' => 'required|in:0,1',
        ];
    }


    public function messages(): array
    {
        return [
            'user_name.required' => '请填写账号',
            'password.required' => '请填写密码',
            'password.alpha_dash' => '密码应由字数字母下划线组成',
            'password.between' => '密码长度应在6-20字符',
            'nick_name.required' => '请填写昵称',
            'user_id.required' => '请填写用户ID',
            'role_id.required' => '请填写角色ID',
            'phonenumber.required' => '请填写手机号',
            'phonenumber.regex' => '手机号格式错误',
            'status.required' => '请填写状态',
            'status.in' => '状态错误',
        ];
    }
}
