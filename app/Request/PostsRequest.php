<?php

namespace App\Request;

use Hyperf\Validation\Request\FormRequest;

class PostsRequest extends FormRequest
{
    protected array $scenes = [
        'id' => ['post_id'],
        'publish' => ['post_type', 'circle_id', 'title', 'content', 'media', 'media_type'],
        'update' => ['post_id', 'circle_id', 'title', 'content', 'media', 'media_type'],
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
            'post_id' => 'required|integer',
            'post_type' => 'required|in:1,2',
            'circle_id' => 'required|integer',
            'user_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'media' => 'array',
            'media_type' => 'in:1,2'
        ];
    }


    public function messages(): array
    {
        return [
            'post_id.required' => '帖子ID不能为空',
            'post_id.integer' => '帖子ID必须为整数',
            'post_type.required' => '帖子类型不能为空',
            'post_type.in' => '帖子类型必须为1或2',
            'circle_id.required' => '关联圈子ID不能为空',
            'circle_id.integer' => '关联圈子ID必须为整数',
            'user_id.required' => '关联用户ID不能为空',
            'user_id.integer' => '关联用户ID必须为整数',
            'title.required' => '标题不能为空',
            'title.string' => '标题必须为字符串',
            'title.max' => '标题最多不能超过255个字符',
            'content.required' => '内容不能为空',
            'content.string' => '内容必须为字符串',
            'media.array' => '媒体必须为数组',
        ];

    }

}