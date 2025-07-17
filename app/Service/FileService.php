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

    protected $allowedImageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff', 'tif', 'svg'];

    protected $allowedVideoExtensions = ['mp4', 'avi', 'wmv', 'flv', 'mkv', 'mov', 'mpg', 'mpeg', 'webm', 'm4v', '3gp', 'ogv', 'ogg', 'mts'];

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
            return $this->uploadSuccess($existFile->upload_id, $existFile->url, $extension);
        }

        // 文件类型校验，只允许图片和视频上传
        $allowedExtensions = array_merge($this->allowedImageExtensions, $this->allowedVideoExtensions);
        if (!in_array(strtolower($extension), $allowedExtensions)) {
            throw new ParametersException('不支持的文件类型');
        }

        // 文件大小限制
        $maxFileSize = 20 * 1024 * 1024; // 20MB
        if ($size > $maxFileSize) {
            throw new ParametersException('文件大小请控制在20M以内');
        }

        // 生成新的文件名和存储路径
        $newFileName = $this->generateFileName($originalName, $extension);
        $storagePath = 'uploads/' . date('Ymd') . '/' . $newFileName;

        // 存储文件
        $stream = fopen($file->getRealPath(), 'r');
        try {
            $this->filesystem->writeStream($storagePath, $stream);
        } catch (\Throwable $throwable) {
            var_dump($throwable->getMessage());
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
            'thumb' => '',
            'ext' => $extension,
            'size' => $size,
            'mime' => $mimeType,
            'md5' => $fileMd5,
            'del_flag' => '0',
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ];

        $uploadId = Db::table('sys_upload')->insertGetId($fileData);

        return $this->uploadSuccess($uploadId, $storagePath, $extension);
    }

    protected function uploadSuccess($uploadId, $storagePath, $extension): array
    {

        $thumb = '';
        $type = 'image';
        if (in_array($extension, $this->allowedVideoExtensions)) {
            $type = 'video';
            $thumb = generateFileUrl($storagePath. '?x-oss-process=video/snapshot,t_1000,f_jpg,w_0,h_0,m_fast');
        }
        return [
            'upload_id' => $uploadId,
            'path' => $storagePath,
            'url' => generateFileUrl($storagePath),
            'type' => $type,
            'thumb' => $thumb,
        ];
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

    public function getFilePathById(int $upload_id): string
    {
        if (empty($upload_id)) {
            return '';
        }
        $path = \Hyperf\DbConnection\Db::table('sys_upload')
            ->where('upload_id', $upload_id)
            ->value('url');
        if ($path) {
            return generateFileUrl($path);
        }
        return '';
    }

    public function getFileInfoById(int $upload_id): object|null
    {
        if (empty($upload_id)) {
            return null;
        }
        $file = \Hyperf\DbConnection\Db::table('sys_upload')
            ->where('upload_id', $upload_id)
            ->select(['upload_id', 'file_name', 'new_file_name', 'url', 'thumb', 'ext', 'size', 'mime'])
            ->first();
        if ($file) {
            $file->url = generateFileUrl($file->url);
            !empty($file->thumb) && $file->thumb = generateFileUrl($file->thumb);
        }
        return null;
    }

    public function getFilepathByIds(array $upload_ids): array
    {
        if (empty($upload_ids)) {
            return [];
        }
        $files = \Hyperf\DbConnection\Db::table('sys_upload')
            ->whereIn('upload_id', $upload_ids)
            ->pluck('url', 'upload_id')
            ->toArray();
        if ($files) {
            foreach ($files as $key => $file) {
                $files[$key] = generateFileUrl($file);
            }
            return $files;
        }
        return [];
    }

    public function getFileInfoByIds(array $upload_ids): array
    {
        if (empty($upload_ids)) {
            return [];
        }
        $files = \Hyperf\DbConnection\Db::table('sys_upload')
            ->whereIn('upload_id', $upload_ids)
            ->select(['upload_id', 'file_name', 'new_file_name', 'url', 'thumb', 'ext', 'size', 'mime'])
            ->get()
            ->toArray();
        if ($files) {
            $result = [];
            foreach ($files as $file) {
                $file->url = generateFileUrl($file->url);
                !empty($file->thumb) && $file->thumb = generateFileUrl($file->thumb);
                $result[$file->upload_id] = $file;
            }
            return $result;
        }
        return [];
    }

}