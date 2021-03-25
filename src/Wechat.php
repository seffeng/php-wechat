<?php
/**
 * @link http://github.com/seffeng/
 * @copyright Copyright (c) 2021 seffeng
 */
namespace Seffeng\Wechat;

use GuzzleHttp\Client;
use Seffeng\Wechat\Handlers\JssdkHandler;
use Seffeng\Wechat\Exceptions\WechatException;
use GuzzleHttp\Exception\RequestException;
use Seffeng\Wechat\Errors\Error;
use Seffeng\Wechat\Contracts\Cache;
use Seffeng\Wechat\Handlers\EncryptHandler;

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
     * @var string
     */
    private $tokenUri = '/cgi-bin/token';

    /**
     *
     * @var string
     */
    private $cacheKeyAccessToken = 'AccessToken:1614009600';

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
        $this->setCache($cache);
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
                $this->handler->setAccessToken($this->getAccessToken());
            } else {
                throw new WechatException('handler class not exists.');
            }
        } else {
            $this->handler = new JssdkHandler($this->getAppid(), $this->getSecret(), $this->getHttpClient(), $this->getCache());
            $this->handler->setAccessToken($this->getAccessToken());
        }
        return $this;
    }

    /**
     *
     * @author zxf
     * @date   2021年2月23日
     * @throws WechatException
     * @throws \Exception
     * @return string
     */
    public function getAccessToken()
    {
        try {
            if (is_object($this->getCache()) && method_exists($this->getCache(), 'get')) {
                if ($data = $this->getCache()->get($this->getCacheKeyAccessToken())) {
                    return $data;
                }
            }
            $request = $this->getHttpClient()->get($this->getTokenUri(), [
                'query' => [
                    'grant_type' => 'client_credential',
                    'appid' => $this->getAppid(),
                    'secret' => $this->getSecret()
                ]
            ]);

            if ($request->getStatusCode() === 200) {
                $body = $request->getBody()->getContents();
                $body = json_decode($body, true);
                if (isset($body['access_token'])) {
                    if (is_object($this->getCache())) {
                        $this->getCache()->set($this->getCacheKeyAccessToken(), $body['access_token'], $body['expires_in'] - 120);
                    }
                    return $body['access_token'];
                } elseif (isset($body['errcode'])) {
                    throw new WechatException((new Error($body['errcode']))->getName());
                }
            }
            throw new WechatException('网络错误或接口请求异常！');
        } catch (RequestException $e) {
            $message = $e->getResponse()->getBody()->getContents();
            if (!$message) {
                $message = $e->getMessage();
            }
            throw new WechatException($message);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     *
     * @author zxf
     * @date   2021年3月25日
     * @param string $key
     * @param string $encryptedData
     * @param string $iv
     * @throws WechatException
     * @throws \Exception
     * @return string
     */
    public function decrypt(string $key, string $encryptedData, string $iv)
    {
        try {
            $handler = new EncryptHandler($this->getAppid(), $key);
            return $handler->decrypt($encryptedData, $iv);
        } catch (WechatException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw $e;
        }
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
        $cache = is_object($cache) ? $cache : ((is_string($cache) && class_exists($cache)) ? (new $cache) : null);
        if (!(is_null($cache) || $cache instanceof Cache)) {
            throw new WechatException('class cacheHandler must instanceof \Seffeng\Wechat\Contracts\Cache .');
        }
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
        return $this->cache;
    }

    /**
     *
     * @author zxf
     * @date   2021年2月23日
     * @param string $uri
     * @return static
     */
    public function setTicketUri(string $uri)
    {
        $this->ticketUri = $uri;
        return $this;
    }

    /**
     *
     * @author zxf
     * @date   2021年2月23日
     * @return string
     */
    public function getTokenUri()
    {
        return $this->tokenUri;
    }


    /**
     *
     * @author zxf
     * @date   2021年2月23日
     * @param mixed $cache
     * @return static
     */
    public function setCacheKeyAccessToken(string $key)
    {
        $this->cacheKeyAccessToken = $key;
        return $this;
    }

    /**
     *
     * @author zxf
     * @date   2021年2月23日
     * @return mixed
     */
    public function getCacheKeyAccessToken()
    {
        return $this->cacheKeyAccessToken;
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
