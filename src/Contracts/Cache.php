<?php
/**
 * @link http://github.com/seffeng/
 * @copyright Copyright (c) 2021 seffeng
 */
namespace Seffeng\Wechat\Contracts;

interface Cache
{
    /**
     *
     * @author zxf
     * @date   2021年3月15日
     * @param string $key
     * @param string $key
     * @param mixed $value
     * @param integer $ttl
     * @return boolean
     */
    public function set(string $key, $value, $ttl = null);

    /**
     *
     * @author zxf
     * @date   2021年3月15日
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null);
}
