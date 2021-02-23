<?php  declare(strict_types=1);

namespace Seffeng\Wechat\Tests;

use Seffeng\Wechat\Wechat;
use Seffeng\Wechat\Exceptions\WechatException;
use PHPUnit\Framework\TestCase;

class WechatTest extends TestCase
{
    public function testJssdk()
    {
        try {
            $appid = '';
            $secret = '';
            $wechat = new Wechat($appid, $secret);
            var_dump($wechat->getAccessToken());
            print_r($wechat->getSignPackage('https://url.cn'));
        } catch (WechatException $e) {
            echo $e->getMessage();
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}
