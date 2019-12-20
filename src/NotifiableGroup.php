<?php

namespace Alone\LaravelXiaomiPush;

use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

class NotifiableGroup
{

    use Notifiable;

    public $items;
    public $app_package;
    public $isIosDevice;

    public $sendTos = [];

    public function __construct($items,$appPackage,$isIosDevice)
    {
        $this->items = new Collection($items);
        $this->app_package = $appPackage;
        $this->isIosDevice = !!$isIosDevice;
    }

    /**
     * 将发送对象按包名及设备分组
     */
    public static function group($notifiables,$chunk = null)
    {
        $gls = collect($notifiables)->reduce(function($dat,$notifiable)
        {
            $pkg = $notifiable->getAppPackage();
            $ios = $notifiable->isIosDevice();
            if(empty($dat["$pkg-$ios"]))
            {
                $dat["$pkg-$ios"] = [
                    'list'    => [],
                    'package' => $pkg,
                    'is_ios'  => $ios,
                ];
            }
            $dat["$pkg-$ios"]['list'][] = $notifiable;
            return $dat;
        },[]);
        return array_reduce($gls,function($gls,$v) use($chunk)
        {
            $nls = (array)data_get($v,'list');
            foreach(isset($chunk) ? array_chunk($nls,$chunk) : [$nls] as $l)
            {
                $gls[] = new static($l,data_get($v,'package'),data_get($v,'is_ios'));
            }
            return $gls;
        },[]);
    }

    public function getAppPackage()
    {
        return $this->app_package;
    }

    public function isIosDevice()
    {
        return $this->isIosDevice;
    }

    public function routeNotificationForXiaomiPush()
    {
        $this->sendTos = $this->getSendTos('xiaomiPush');
        return $this->sendTos;
    }

    protected function getSendTos($router)
    {
        return $this->items->reduce(function($lst,$notifiable) use($router)
        {
            if(is_object($notifiable) && method_exists($notifiable,'routeNotificationFor'))
            {
                $sto = $notifiable->routeNotificationFor($router);
                if($sto)
                {
                    $lst[] = $sto;
                }
            }
            return $lst;
        },[]);
    }

}
