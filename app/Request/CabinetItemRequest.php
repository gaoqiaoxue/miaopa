<?php

declare(strict_types=1);

namespace App\Request;

use Hyperf\Validation\Request\FormRequest;

class CabinetItemRequest extends FormRequest
{
    protected array $scenes = [
        'id' => ['item_id'],
        'add' => ['cabinet_id', 'name', 'alias', 'number', 'images'],
        'edit' => ['item_id', 'name', 'alias', 'number', 'images'],
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
            'cabinet_id' => 'required|integer',
            'images' => 'required|array',
            'name' => 'required',
            'alias' => 'required',
            'number' => 'required|integer',
            'item_id' => 'required|integer',
        ];
    }


    public function messages(): array
    {
        return [
            'cabinet_id.required' => '请选择次元柜',
            'cabinet_id.integer' => '次元柜ID不正确',
            'images.required' => '请上传图片',
            'images.array' => '请上传图片',
            'name.required' => '请填写名称',
            'alias.required' => '请填写别名',
            'number.required' => '请填写藏品数',
            'number.integer' => '藏品数不正确',
            'item_id.required' => '请选择藏品',
            'item_id.integer' => '藏品ID不正确',
        ];

    }
}