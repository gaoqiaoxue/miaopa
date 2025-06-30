<?php

namespace App\Service;

use App\Exception\ParametersException;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Cache\Annotation\CacheEvict;
use Hyperf\DbConnection\Db;

class ConfigService
{
    #[Cacheable(prefix: 'system_config', ttl: 3600)]
    public function getConfig()
    {
        $info = Db::table('system_config')
            ->where('id', '=', 1)
            ->select(['post_publish_type', 'comment_publish_type', 'report_publish_type', 'daily_sign_coins', 'continuous_sign_config',
                'post_coins', 'comment_coins', 'activity_coins', 'stay_time_config'])
            ->first();
        $info->continuous_sign_config = json_decode($info->continuous_sign_config, true);
        $info->stay_time_config = json_decode($info->stay_time_config, true);
        return $info;
    }

    #[CacheEvict(prefix: 'system_config', value: "")]
    public function update($params)
    {
        isset($params['continuous_sign_config']) && $params['continuous_sign_config'] = $this->setJson($params['continuous_sign_config']);
        isset($params['stay_time_config']) && $params['stay_time_config'] = $this->setJson($params['stay_time_config']);
        return Db::table('system_config')->where('id', '=', 1)->update($params);
    }

    public function getValue($key)
    {
        $config = $this->getConfig();
        return isset($config->$key) ? $config->$key : null;
    }

    protected function setJson(mixed $value): string
    {
        if (empty($value)) {
            return json_encode([]);
        }
        foreach ($value as $k => $v) {
            if (empty($v['time']) || !is_numeric($v['time'])) {
                throw new ParametersException('请填写时间');
            }
            if (empty($v['coins']) || !is_numeric($v['coins'])) {
                throw new ParametersException('请填写币值');
            }
        }
        // 按照time从小到大排序
        usort($value, function ($a, $b) {
            return $a['time'] - $b['time'];
        });
        return json_encode($value);
    }
}