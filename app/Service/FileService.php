<?php

namespace App\Service;

use App\Exception\ParametersException;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use League\Flysystem\Filesystem;

class FileService
{
    #[Inject]
    protected Filesystem $filesystem;

    /**
     * 上传文件并保存记录到数据库
     *
     * @param \Hyperf\HttpMessage\Upload\UploadedFile $file 上传的文件对象
     * @param string $createBy 创建人
     * @return array 返回文件信息
     */
    public function upload($file): array
    {
        if (!$file->isValid()) {
            throw new ParametersException('上传文件无效');
        }

        // 获取文件信息
        $originalName = $file->getClientFilename();
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $mimeType = $file->getClientMediaType();
        $size = $file->getSize();
        $fileMd5 = md5_file($file->getRealPath());

        // 检查文件是否已存在
        $existFile = Db::table('sys_upload')->where('md5', $fileMd5)->first();
        if ($existFile) {
            return (array)$existFile;
        }

        // 生成新的文件名和存储路径
        $newFileName = $this->generateFileName($originalName, $extension);
        $storagePath = 'uploads/' . date('Ymd') . '/' . $newFileName;

        // 存储文件
        $stream = fopen($file->getRealPath(), 'r');
        try {
            $this->filesystem->writeStream($storagePath, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        // 保存到数据库
        $fileData = [
            'file_name' => $originalName,
            'new_file_name' => $newFileName,
            'url' => $storagePath,
            'thumb' => '', // TODO 视频自动生成封面
            'ext' => $extension,
            'size' => $size,
            'mime' => $mimeType,
            'md5' => $fileMd5,
            'del_flag' => '0',
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ];

        $uploadId = Db::table('sys_upload')->insertGetId($fileData);
        $fileData['upload_id'] = $uploadId;

        return $fileData;
    }

    /**
     * 生成唯一的文件名
     *
     * @param string $originalName 原始文件名
     * @param string $extension 文件扩展名
     * @return string
     */
    protected function generateFileName(string $originalName, string $extension): string
    {
        $name = pathinfo($originalName, PATHINFO_FILENAME);
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '', $name);
        return $safeName . '_' . uniqid() . '.' . $extension;
    }

}