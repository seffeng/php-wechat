<?php
/**
 * @link http://github.com/seffeng/
 * @copyright Copyright (c) 2021 seffeng
 */
namespace Seffeng\Wechat;

use GuzzleHttp\Client;
use Seffeng\Wechat\Exceptions\WechatException;
use GuzzleHttp\Exception\RequestException;
use Seffeng\Wechat\Errors\Error;

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
    private $ticketUri = '/cgi-bin/ticket/getticket';

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
     * @var string
     */
    private $cacheKeyAccessToken = 'AccessToken:1614009600';

    /**
     *
     * @var string
     */
    private $cacheKeyJsapiTicket = 'JsapiTicket:1614009600';

    /**
     *
     * @var integer
     */
    private $cacheTtl;

    /**
     *
     * @author zxf
     * @date   2021年2月23日
     * @param string $appid
     * @param string $appSecret
     */
    public function __construct(string $appid, string $appSecret)
    {
        $this->appid = $appid;
        $this->appSecret = $appSecret;
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

            $params = [
                'jsapi_ticket' => $jsapiTicket,
                'noncestr' => $nonceStr,
                'timestamp' => $timestamp,
                'url' => $url,
            ];
            ksort($params);
            $string = http_build_query($params);
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
        return $this->cache;
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
