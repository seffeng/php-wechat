<?php
/**
 * @link http://github.com/seffeng/
 * @copyright Copyright (c) 2021 seffeng
 */
namespace Seffeng\Wechat\Handlers;

use Seffeng\Wechat\Exceptions\WechatException;

class EncryptHandler
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
    private $sessionKey;

    /**
     *
     * @author zxf
     * @date   2021年3月25日
     * @param string $appid
     */
    public function __construct(string $appid, string $sessionKey)
    {
        $this->appid = $appid;
        $this->sessionKey = $sessionKey;
    }

    /**
     *
     * @author zxf
     * @date   2021年3月25日
     * @param string $encryptedData
     * @param string $iv
     * @throws WechatException
     * @return array
     */
    public function decrypt(string $encryptedData, string $iv)
    {
        if (strlen($this->sessionKey) != 24) {
            throw new WechatException('解密KEY sessionKey 错误！');
        }
        if (strlen($iv) != 24) {
            throw new WechatException('加密算法的初始向量 iv  错误！');
        }

        $aesKey = base64_decode($this->sessionKey);
        $aesIV = base64_decode($iv);
        $aesCipher = base64_decode($encryptedData);
        $result = openssl_decrypt($aesCipher, 'AES-128-CBC', $aesKey, 1, $aesIV);
        $data = json_decode($result);

        if (is_null($data)){
            throw new WechatException('解密失败！');
        } elseif ($data->watermark->appid != $this->appid) {
            throw new WechatException('appid 不匹配！');
        }

        return [
            'phoneNumber' => $data->phoneNumber,
            'purePhoneNumber' => $data->purePhoneNumber,
            'countryCode' => $data->countryCode,
        ];
    }
}
