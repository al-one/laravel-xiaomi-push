<?php

namespace Alone\LaravelXiaomiPush;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades;
use xmpush;

class XiaomiPushChannel
{

    protected $config;

    public function __construct($cfg = 'xiaomi_push')
    {
        is_string($cfg) && $cfg = config("services.$cfg") ?: [];
        $this->config = (array)$cfg;
    }

    /**
     * 发送小米推送
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return mixed
     */
    public function send($notifiable,Notification $notification)
    {
        $dvc = 'android';
        $pkg = null;
        if(is_object($notifiable) && method_exists($notifiable,'routeNotificationFor'))
        {
            if(!$sto = $notifiable->routeNotificationFor('xiaomiPush'))
            {
                return false;
            }
            if(method_exists($notifiable,'isIosDevice') && $notifiable->isIosDevice())
            {
                $dvc = 'ios';
            }
            $pkg = $this->getAppPackage($notifiable);
        }
        else
        {
            $sto = $notifiable;
        }
        $ios = $dvc == 'ios';
        $cfg = $this->getConfig($pkg,$dvc);
        xmpush\Constants::setPackage($pkg);// Builder 之前设置包名
        xmpush\Constants::setBundleId($pkg);
        xmpush\Constants::setSecret(data_get($cfg,'secret'));
        if($ios)
        {
            if(data_get($cfg,'sandbox'))
            {
                xmpush\Constants::useSandbox();
            }
            $msg = new xmpush\IOSBuilder();
        }
        else
        {
            $msg = new xmpush\Builder();
        }
        /** @var Notification|XiaomiNotification $notification */
        $msg = $notification->toXiaomiPush($notifiable,$msg,$cfg);
        $msg->build();
        $sender = new xmpush\Sender;
        $sendBy = $notification->sendBy() ?: data_get($cfg,'send_by');
        if($sendBy == 'regid')
        {
            $ret = $sender->sendToIds($msg,(array)$sto);
        }
        elseif($sendBy == 'alias')
        {
            $ret = $sender->sendToAliases($msg,(array)$sto);
        }
        else
        {
            $ret = $sender->sendToUserAccounts($msg,(array)$sto);
        }
        $raw = $ret->getRaw();
        if($eno = $ret->getErrorCode())
        {
            Facades\Log::warning("xiaomi push error \t",compact('eno','sto','dvc','pkg','raw','cfg'));
        }
        else
        {
            $dat = $msg->getFields();
            Facades\Log::debug("xiaomi push success \t",compact('dat','sto','dvc','pkg','raw'));
        }
        return $ret;
    }

    public function getAppPackage($notifiable)
    {
        if(method_exists($notifiable,'getAppPackage'))
        {
            $pkg = $notifiable->getAppPackage();
        }
        else
        {
            $pkg = data_get($notifiable,'app_package');
        }
        return $pkg;
    }

    public function getConfig($pkg = null,$dvc = null)
    {
        $cfg = $this->config ?: [];
        if(!empty($dvc) && isset($cfg[$dvc]))
        {
            $cfg = ($cfg[$dvc] ?: []) + $cfg;
        }
        if(!empty($pkg))
        {
            // 多包名不同配置
            if(isset($cfg['bundles'][$pkg]))
            {
                $cfg = ($cfg['bundles'][$pkg] ?: []) + $cfg;
            }
            elseif(isset($this->config['bundles'][$pkg]))
            {
                $cfg = ($this->config['bundles'][$pkg] ?: []) + $cfg;
            }
        }
        return Arr::except($cfg,['android','ios','bundles']);
    }

}
