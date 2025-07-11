<?php

namespace App\Request;

use Hyperf\Validation\Request\FormRequest;

class CommentRequest extends FormRequest
{
    protected array $scenes = [
        'id' => ['comment_id'],
        'set_top' => ['comment_id', 'is_top'],
        'comment' => ['post_id', 'content', 'images'],
        'reply' => ['parent_id', 'content', 'images'],
        'like' => ['comment_id', 'status']
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
            'parent_id' => 'required|integer',
            'content' => 'required|string',
            'images' => 'array',
            'comment_id' => 'required|integer',
            'is_top' => 'in:0,1',
            'status' => 'in:0,1',
        ];
    }


    public function messages(): array
    {
        return [
            'post_id.required' => '帖子ID不能为空',
            'post_id.integer' => '帖子ID必须为整数',
            'parent_id.required' => '父评论ID不能为空',
            'parent_id.integer' => '父评论ID必须为整数',
            'content.required' => '评论内容不能为空',
            'content.string' => '评论内容必须为字符串',
            'images.array' => '图片必须为数组',
            'comment_id.required' => '评论ID不能为空',
            'comment_id.integer' => '评论ID必须为整数',
            'is_top.in' => '顶置评论必须为0或1',
            'status.in' => '点赞状态必须为0或1',
        ];

    }

}