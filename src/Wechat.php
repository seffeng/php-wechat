<?php
/**
 * @link http://github.com/seffeng/
 * @copyright Copyright (c) 2021 seffeng
 */
namespace Seffeng\Wechat;

use GuzzleHttp\Client;
use Seffeng\Wechat\Handlers\JssdkHandler;
use Seffeng\Wechat\Exceptions\WechatException;

/**
 *
 * @author zxf
 * @date   2021年2月23日
 */
class Wechat
{
    /**
     *
     * @var string
     */
    private $appid;

    /**
     *
     * @var string
     */
    private $appSecret;

    /**
     *
     * @var string
     */
    private $baseApi = 'https://api.weixin.qq.com';

    /**
     *
     * @var mixed
     */
    private $handler;

    /**
     *
     * @var integer
     */
    private $timeout = 5;

    /**
     *
     * @var mixed
     */
    private $cache;

    /**
     *
     * @author zxf
     * @date   2021年2月23日
     * @param string $appid
     * @param string $appSecret
     * @param mixed $handler
     * @param mixed $cache
     */
    public function __construct(string $appid, string $appSecret, $handler = null, $cache = null)
    {
        $this->appid = $appid;
        $this->appSecret = $appSecret;
        $this->cache = $cache;
        $this->loadHandler($handler);
    }

    /**
     *
     * @author zxf
     * @date   2021年2月24日
     * @param mixed $handler
     * @throws WechatException
     * @return static
     */
    public function loadHandler($handler = null)
    {
        if (!is_null($handler)) {
            if (class_exists($handler)) {
                $this->handler = new $handler($this->getAppid(), $this->getSecret(), $this->getHttpClient(), $this->getCache());
            } else {
                throw new WechatException('handler class not exists.');
            }
        } else {
            $this->handler = new JssdkHandler($this->getAppid(), $this->getSecret(), $this->getHttpClient(), $this->getCache());
        }
        return $this;
    }

    /**
     *
     * @author zxf
     * @date   2021年2月23日
     * @param string $appid
     * @return static
     */
    public function setAppid(string $appid)
    {
        $this->appid = $appid;
        return $this;
    }

    /**
     *
     * @author zxf
     * @date   2021年2月23日
     * @return string
     */
    public function getAppid()
    {
        return $this->appid;
    }

    /**
     *
     * @author zxf
     * @date   2021年2月23日
     * @param string $appSecret
     * @return static
     */
    public function setSecret(string $appSecret)
    {
        $this->appSecret = $appSecret;
        return $this;
    }

    /**
     *
     * @author zxf
     * @date   2021年2月23日
     * @return string
     */
    public function getSecret()
    {
        return $this->appSecret;
    }

    /**
     *
     * @author zxf
     * @date   2021年2月23日
     * @param int $second
     * @return static
     */
    public function setTimeout(int $second)
    {
        $this->timeout = $second;
        return $this;
    }

    /**
     *
     * @author zxf
     * @date   2021年2月23日
     * @return number
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     *
     * @author zxf
     * @date   2021年2月23日
     * @param string $uri
     * @return static
     */
    public function setBaseApi(string $baseApi)
    {
        $this->baseApi = $baseApi;
        return $this;
    }

    /**
     *
     * @author zxf
     * @date   2021年2月23日
     * @return string
     */
    public function getBaseApi()
    {
        return $this->baseApi;
    }

    /**
     *
     * @author zxf
     * @date   2021年2月23日
     * @param mixed $cache
     * @return static
     */
    public function setCache($cache)
    {
        $this->cache = $cache;
        return $this;
    }

    /**
     *
     * @author zxf
     * @date   2021年2月23日
     * @return mixed
     */
    public function getCache()
    {
        return is_object($this->cache) ? $this->cache : ((is_string($this->cache) && class_exists($this->cache)) ? (new $this->cache) : null);
    }

    /**
     *
     * @author zxf
     * @date   2021年2月24日
     * @param string $method
     * @param mixed $parameters
     * @throws WechatException
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        try {
            if (method_exists($this->handler, $method)) {
                return $this->handler->{$method}(...$parameters);
            } else {
                throw new WechatException('方法｛' . $method . '｝不存在！');
            }
        } catch (WechatException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new WechatException('异常错误：确认方法｛' . $method . '｝是否存在！');
        }
    }

    /**
     *
     * @author zxf
     * @date   2021年2月23日
     * @return Client
     */
    private function getHttpClient(string $baseApi = null)
    {
        return new Client([
            'base_uri' => is_null($baseApi) ? $this->getBaseApi() : $baseApi,
            'timeout' => $this->getTimeout()
        ]);
    }
}
