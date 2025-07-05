<?php

declare(strict_types=1);

namespace App\Request;

use Hyperf\Validation\Request\FormRequest;

class CabinetRequest extends FormRequest
{
    protected array $scenes = [
        'id' => ['cabinet_id'],
        'add' => ['cover', 'name', 'cabinet_type', 'is_public'],
        'edit' => ['cabinet_id', 'cover', 'name', 'cabinet_type', 'is_public'],
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
            'cover' => 'required',
            'name' => 'required',
            'cabinet_type' => 'required|in:1,2,3,99',
            'is_public' => 'required|in:0,1',
            'cabinet_id' => 'required|integer',
        ];
    }


    public function messages(): array
    {
        return [
            'cover.required' => '请上传封面',
            'name.required' => '请填写名称',
            'cabinet_type.required' => '请选择类型',
            'cabinet_type.in' => '类型不正确',
            'is_public.required' => '请选择是否公开',
            'is_public.in' => '是否公开不正确',
            'cabinet_id.required' => '请选择次元柜',
            'cabinet_id.integer' => '次元柜ID不正确',
        ];

    }
}