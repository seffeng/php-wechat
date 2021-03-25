<?php
/**
 * @link http://github.com/seffeng/
 * @copyright Copyright (c) 2021 seffeng
 */
namespace Seffeng\Wechat\Handlers;

use Seffeng\Wechat\Exceptions\WechatException;
use Seffeng\Wechat\Errors\Error;
use GuzzleHttp\Exception\RequestException;

class JssdkHandler
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
    private $ticketUri = '/cgi-bin/ticket/getticket';

    /**
     *
     * @var string
     */
    private $cacheKeyJsapiTicket = 'JsapiTicket:1614009600';

    /**
     *
     * @var mixed
     */
    private $cache;

    /**
     *
     * @var mixed
     */
    private $httpClient;

    /**
     *
     * @author zxf
     * @date   2021年2月24日
     * @param string $appid
     * @param string $appSecret
     * @param mixed $cache
     * @param int $ttl
     */
    public function __construct(string $appid, string $appSecret, $httpClient, $cache = null)
    {
        $this->appid = $appid;
        $this->appSecret = $appSecret;
        $this->httpClient = $httpClient;
        $this->cache = $cache;
    }

    /**
     *
     * @author zxf
     * @date   2021年3月25日
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     *
     * @author zxf
     * @date   2021年3月25日
     * @param string $token
     * @return static
     */
    public function setAccessToken(string $token)
    {
        $this->accessToken = $token;
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
     * @param string $uri
     * @return static
     */
    public function setTokenUri(string $uri)
    {
        $this->tokenUri = $uri;
        return $this;
    }

    /**
     *
     * @author zxf
     * @date   2021年2月23日
     * @return string
     */
    public function getTicketUri()
    {
        return $this->ticketUri;
    }

    /**
     *
     * @author zxf
     * @date   2021年2月23日
     * @param mixed $cache
     * @return static
     */
    public function setCacheKeyJsapiTicket(string $key)
    {
        $this->cacheKeyJsapiTicket = $key;
        return $this;
    }

    /**
     *
     * @author zxf
     * @date   2021年2月23日
     * @return mixed
     */
    public function getCacheKeyJsapiTicket()
    {
        return $this->cacheKeyJsapiTicket;
    }

    /**
     *
     * @author zxf
     * @date   2021年2月23日
     * @throws WechatException
     * @throws \Exception
     * @return mixed
     */
    public function getJsapiTicket()
    {
        try {
            if (is_object($this->getCache()) && method_exists($this->getCache(), 'get')) {
                if ($data = $this->getCache()->get($this->getCacheKeyJsapiTicket())) {
                    return $data;
                }
            }
            $request = $this->getHttpClient()->get($this->getTicketUri(), [
                'query' => [
                    'type' => 'jsapi',
                    'access_token' => $this->getAccessToken()
                ]
            ]);

            if ($request->getStatusCode() === 200) {
                $body = $request->getBody()->getContents();
                $body = json_decode($body, true);
                if (isset($body['ticket'])) {
                    if (is_object($this->getCache())) {
                        $this->getCache()->set($this->getCacheKeyJsapiTicket(), $body['ticket'], $body['expires_in'] - 120);
                    }
                    return $body['ticket'];
                } elseif (isset($body['errcode']) && isset($body['errcode']) > 0) {
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
     * @date   2021年2月23日
     * @param string $url
     * @throws \Exception
     * @return array
     */
    public function getSignPackage(string $url)
    {
        try {
            $jsapiTicket = $this->getJsapiTicket();
            $timestamp = time();
            $nonceStr = $this->createNonceStr();
            $string = 'jsapi_ticket=' . $jsapiTicket . '&noncestr=' . $nonceStr . '&timestamp=' . $timestamp . '&url=' . $url;
            $signature = sha1($string);

            return [
                'appId'     => $this->getAppid(),
                'nonceStr'  => $nonceStr,
                'timestamp' => $timestamp,
                'url'       => $url,
                'signature' => $signature,
                'rawString' => $string
            ];
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     *
     * @author zxf
     * @date   2021年2月24日
     * @param string $method
     * @param mixed $parameters
     * @throws WechatException
     */
    public function __call($method, $parameters)
    {
        throw new WechatException('方法｛' . $method . '｝不存在！');
    }

    /**
     *
     * @author zxf
     * @date   2021年2月24日
     * @return mixed
     */
    private function getCache()
    {
        return $this->cache;
    }

    /**
     *
     * @author zxf
     * @date   2021年2月23日
     * @param number $length
     * @return string
     */
    private function createNonceStr($length = 16)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     *
     * @author zxf
     * @date   2021年2月24日
     * @return mixed
     */
    private function getHttpClient()
    {
        return $this->httpClient;
    }
}
