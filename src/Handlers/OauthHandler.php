<?php
/**
 * @link http://github.com/seffeng/
 * @copyright Copyright (c) 2021 seffeng
 */
namespace Seffeng\Wechat\Handlers;

use Seffeng\Wechat\Exceptions\WechatException;
use Seffeng\Wechat\Errors\Error;
use GuzzleHttp\Exception\RequestException;

class OauthHandler
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
    private $jscode2sessionUri = '/sns/jscode2session';

    /**
     *
     * @var mixed
     */
    private $httpClient;

    /**
     *
     * @var string
     */
    private $accessToken;

    /**
     *
     * @author zxf
     * @date   2021年3月25日
     * @param string $appid
     * @param string $appSecret
     * @param mixed $httpClient
     */
    public function __construct(string $appid, string $appSecret, $httpClient)
    {
        $this->appid = $appid;
        $this->appSecret = $appSecret;
        $this->httpClient = $httpClient;
    }

    /**
     *
     * @author zxf
     * @date   2021年3月25日
     * @param string $jscode
     * @throws WechatException
     * @throws \Exception
     */
    public function jscode2session(string $jscode)
    {
        try {
            $request = $this->getHttpClient()->get($this->getJscode2sessionUri(), [
                'query' => [
                    'grant_type' => 'authorization_code',
                    'appid' => $this->getAppid(),
                    'secret' => $this->getSecret(),
                    'js_code' => $jscode
                ]
            ]);
            if ($request->getStatusCode() === 200) {
                $body = $request->getBody()->getContents();
                $body = json_decode($body, true);
                if (isset($body['openid'])) {
                    return [
                        'openid' => $body['openid'],
                        'sessionKey' => $body['session_key']
                    ];
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
     * @return string
     */
    public function getAppid()
    {
        return $this->appid;
    }

    /**
     *
     * @author zxf
     * @date   2021年3月25日
     * @return string
     */
    public function getSecret()
    {
        return $this->appSecret;
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
     * @date   2021年3月25日
     * @param string $uri
     * @return static
     */
    public function setJscode2sessionUri(string $uri)
    {
        $this->jscode2sessionUri = $uri;
        return $this;
    }

    /**
     *
     * @author zxf
     * @date   2021年3月25日
     * @return string
     */
    public function getJscode2sessionUri()
    {
        return $this->jscode2sessionUri;
    }

    /**
     *
     * @author zxf
     * @date   2021年3月25日
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
     * @date   2021年3月25日
     * @return mixed
     */
    private function getHttpClient()
    {
        return $this->httpClient;
    }
}
