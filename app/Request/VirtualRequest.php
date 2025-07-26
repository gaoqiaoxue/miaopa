<?php

declare(strict_types=1);

namespace App\Request;

use Hyperf\Validation\Request\FormRequest;

class VirtualRequest extends FormRequest
{
    protected array $scenes = [
        'add' => ['name', 'item_type', 'is_default', 'exchange_amount', 'valid_days', 'quantity', 'image', 'avatar', 'weight', 'status'],
        'edit' => ['virtual_id', 'name', 'item_type', 'is_default', 'exchange_amount', 'valid_days', 'quantity', 'image', 'avatar', 'weight', 'status'],
        'id' => ['virtual_id'],
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
            'virtual_id' => 'required|integer',
            'name' => 'required',
            'item_type' => 'required|in:1,2',
            'is_default' => 'in:0,1',
            'exchange_amount' => 'required|integer',
            'valid_days' => 'required|integer',
            'quantity' => 'required|integer',
            'image' => 'required|string',
            'avatar' => 'string',
            'weight' => 'integer',
            'status' => 'in:0,1',
        ];
    }


    public function messages(): array
    {
        return [
            'virtual_id.required' => 'ID必填',
            'virtual_id.integer' => 'ID必须为整数',
            'name.required' => '名称必填',
            'item_type.required' => '类型必填',
            'item_type.in' => '类型必须为1或2',
            'is_default.required' => '是否默认必填',
            'is_default.in' => '是否默认必须为0或1',
            'exchange_amount.required' => '兑换金额必填',
            'exchange_amount.integer' => '兑换金额必须为整数',
            'valid_days.required' => '有效天数必填',
            'valid_days.integer' => '有效天数必须为整数',
            'quantity.required' => '数量必填',
            'quantity.integer' => '数量必须为整数',
            'image.required' => '图标必填',
            'image.string' => '图标必须为字符串',
            'avatar.required' => '头像必填',
            'avatar.string' => '头像必须为字符串',
            'weight.integer' => '权重必须为整数',
            'status.in' => '状态必须为整数',
        ];
    }
}
