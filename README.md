# XiaoMi Push For Laravel Notifications

## Installing

```sh
$ composer require "al-one/laravel-xiaomi-push" -vvv
```


## Config

```php
# optional if >= 5.5
# config/app.php
<?php

return [

    'providers' => [
        Alone\LaravelXiaomiPush\ServiceProvider::class,
    ],

];
```

```php
# config/services.php
[
    'xiaomi_push' => [
        'send_by' => 'account',
        'android' => [
            'bundle_id' => 'com.app.bundle_id',
            'appid'     => '1234567890123456',
            'key'       => '1234567890123456',
            'secret'    => 'abcdefghijklmn==',
        ],
        'ios' => [
            'bundle_id' => 'com.app.bundle_id',
            'appid'     => '1234567890123456',
            'key'       => '1234567890123456',
            'secret'    => 'abcdefghijklmn==',
            'sandbox'   => false,
        ],
    ],
];
```


## Usage

```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class User extends Model
{

    use Notifiable;

    /**
     * 小米推送路由
     */
    public function routeNotificationForXiaoMiPush()
    {
        return $this->getKey();
    }
    
    /**
     * 如果不同用户所属的APP包名可能不同，请添加此方法
     */
    public function getAppPackage()
    {
        return 'com.app.bundle_id';
    }
    
    /**
     * 添加此方法以判断用户是否为苹果设备用户
     */
    public function isIosDevice()
    {
        return true;
    }

}
```

```php
<?php
use Illuminate\Support\Facades\Notification;
use Alone\LaravelXiaomiPush\XiaomiNotification;

$msg = (new XiaomiNotification)
    ->title('通知标题')
    ->description('通知描述')
    ->body('通知描述 For iOS')
    ->payload([
         'action' => 'openApp',
    ])
    ->sendBy('account')
    ->setHandler(function($msg,$notifiable,$cfg,$ios)
    {
        /**
         * @link https://github.com/al-one/xmpush-php/blob/master/sdk/xmpush/Builder.php
         * @link https://github.com/al-one/xmpush-php/blob/master/sdk/xmpush/IOSBuilder.php
         */
        if($ios)
        {
            $msg->badge(1);
            $msg->soundUrl('ring.mp3');
        }
        else
        {
            $msg->notifyId(rand(0,4));
        }
    });

$user->notify($msg);
Notification::send($users,$msg);
```


## License

MIT