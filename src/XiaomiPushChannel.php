<?php

namespace Alone\LaravelXiaomiPush;

use Illuminate\Notifications\Notification;
use xmpush;

class XiaomiPushChannel
{

    protected $config;

    public function __construct($cfg = [])
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
        if(is_object($notifiable) && method_exists($notifiable,'routeNotificationFor'))
        {
            if(!$sto = $notifiable->routeNotificationFor('xiaomiPush'))
            {
                return false;
            }
        }
        else
        {
            $sto = $notifiable;
        }
        /** @var Notification|XiaomiNotification $notification */
        $msg = $notification->toXiaoMiPush($notifiable,$this->config);
        $sender = new xmpush\Sender;
        $sendBy = $notification->sendBy() ?: data_get($this->config,'send_by');
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
            $cfg = $this->config;
            \Log::error("xiaomi push error \t",compact('eno','raw','cfg'));
        }
        else
        {
            \Log::debug("xiaomi push success \t",[compact('msg','notifiable','raw')]);
        }
        return $ret;
    }

}
