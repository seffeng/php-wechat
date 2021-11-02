## SmsClient

# 当前版本不好用

```shell
# 当前版本不好用
```

### 安装

```
$ composer require seffeng/wechat
```

### 目录说明

```
├─src
│  │  Wechat.php
│  ├─Errors
│  │      Error.php
│  ├─Exceptions
│  │      WechatException.php
│  └─Helpers
│         ArrayHelper.php
└─tests
    WechatTest.php
```

### 示例

```php
/**
 * SiteController
 */
use Seffeng\Wechat\Wechat;
use Seffeng\Wechat\Exceptions\WechatException;

class SiteController extends Controller
{
    public function index()
    {
        try {
            $appid = '';
            $secret = '';
            
            /**
             * 缓存处理类，提供 get($key) 和 set($key, $value, $ttl) 方法
             *
             * 可实现接口 Seffeng\Wechat\Contracts\Cache
             */
            $cacheClass = null;
            $wechat = new Wechat($appid, $secret, null, $cacheClass);
            var_dump($wechat->getAccessToken());
            var_dump($wechat->getJsapiTicket());
            print_r($wechat->getSignPackage('https://url.cn'));
        } catch (WechatException $e) {
            echo $e->getMessage();
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}
```

### 备注

1、测试脚本 tests/WechatTest.php 仅作为示例供参考。

