<?php
// app/Service/ImageService.php

declare(strict_types=1);

namespace App\Service;

use GuzzleHttp\Client;
use Hyperf\Filesystem\FilesystemFactory;
use Hyperf\Guzzle\ClientFactory;

class ImageService
{
    private Client $httpClient;

    public function __construct(
        private FilesystemFactory $filesystemFactory,
        ClientFactory $clientFactory
    ) {
        $this->httpClient = $clientFactory->create();
    }

    /**
     * 下载远程图片并保存到OSS
     *
     * @param string $url 图片URL
     * @param string $savePath OSS保存路径
     * @return string OSS文件URL
     * @throws \Exception
     */
    public function saveImageToOss(string $url, string $savePath): string
    {
        try {
            // 下载图片
            $response = $this->httpClient->get($url);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Failed to download image');
            }

            $imageContent = $response->getBody()->getContents();

            // 获取文件扩展名
            $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
            $filename = $savePath . '/' . uniqid() . '.' . $extension;

            // 保存到OSS
            $filesystem = $this->filesystemFactory->get('oss');
            $filesystem->write($filename, $imageContent);

            // 获取OSS文件URL
            return $filename;
        } catch (\Throwable $e) {
            throw new \Exception('Failed to save image to OSS: ' . $e->getMessage());
        }
    }
}