<?php

namespace Alone\LaravelXiaomiPush;

use Illuminate\Support;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use SuperClosure\SerializableClosure;
use xmpush;

class XiaomiNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $message = [];

    protected $channels = ['xiaomi_push'];

    /**
     * @var Notifiable
     */
    protected $notifiable;

    protected $sendBy;

    /**
     * @var \Closure|SerializableClosure
     */
    protected $handler;

    public function __construct($message = [])
    {
        $this->message = $message;
    }

    public function title($set = null)
    {
        return $this->getOrSet(__FUNCTION__,$set);
    }

    public function description($set = null)
    {
        return $this->getOrSet(__FUNCTION__,$set);
    }

    public function body($set = null)
    {
        return $this->getOrSet(__FUNCTION__,$set);
    }

    public function content($set = null)
    {
        if(isset($set))
        {
            $this->body($set);
            $this->description($set);
            return $this;
        }
        return $this->body() ?: $this->description();
    }

    public function payload($set = null)
    {
        return $this->getOrSet(__FUNCTION__,$set);
    }

    protected function getOrSet($key,$val = null)
    {
        if(isset($val))
        {
            data_set($this->message,$key,$val);
            return $this;
        }
        return data_get($this->message,$key);
    }

    /**
     * 发送方式
     * regid/alias/account
     */
    public function sendBy($set = null)
    {
        if(isset($set))
        {
            $this->sendBy = $set;
            return $this;
        }
        return $this->sendBy;
    }

    /**
     * 推送频道
     */
    public function via($notifiable)
    {
        $this->notifiable = $notifiable;
        if(is_object($notifiable))
        {
            if($notifiable->routeNotificationFor('xiaomiPush'))
            {
                $this->channels('xiaomi_push');
            }
        }
        return $this->channels();
    }

    public function channels($set = null)
    {
        if(isset($set))
        {
            if(is_array($set))
            {
                $this->channels = $set;
            }
            else
            {
                $this->channels[] = $set;
            }
            $this->channels = array_unique($this->channels);
            return $this;
        }
        return $this->channels;
    }

    public function getConfig($dvc = null,$pkg = null,$config = [])
    {
        $cfg = $config ?: [];
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
            elseif(isset($config['bundles'][$pkg]))
            {
                $cfg = ($config['bundles'][$pkg] ?: []) + $cfg;
            }
        }
        return Support\Arr::except($cfg,['android','ios','bundles']);
    }

    /**
     * 小米推送
     */
    public function toXiaoMiPush($notifiable,$cfg = [])
    {
        $ios = false;
        if(method_exists($notifiable,'isIosDevice'))
        {
            $ios = $notifiable->isIosDevice();
        }
        $dvc = $ios ? 'ios' : 'android';
        if(method_exists($notifiable,'getAppPackage'))
        {
            $pkg = $notifiable->getAppPackage();
        }
        else
        {
            $pkg = data_get($notifiable,'app_package');
        }
        $cfg = $this->getConfig($dvc,$pkg,$cfg);
        if(empty($pkg))
        {
            $pkg = data_get($cfg,'bundle_id');
        }
        xmpush\Constants::setPackage($pkg);// Builder 之前设置包名
        xmpush\Constants::setBundleId($pkg);
        xmpush\Constants::setSecret(data_get($cfg,'secret'));
        $payload = $this->payload();
        if($ios)
        {
            if(data_get($cfg,'sandbox'))
            {
                xmpush\Constants::useSandbox();
            }
            $msg = new xmpush\IOSBuilder();
            if($this->body())
            {
                $msg->body($this->body());
            }
            if($payload)
            {
                $msg->extra('payload',json_encode($payload));
            }
        }
        else
        {
            $msg = new xmpush\Builder();
            $msg->passThrough(0);
            if($payload)
            {
                $msg->payload(json_encode($payload));
            }
            //$msg->extra(xmpush\Builder::notifyEffect,1/*打开APP*/);
            $msg->extra(xmpush\Builder::notifyForeground,0);
        }
        foreach(['title','description'] as $fun)
        {
            $val = $this->$fun();
            $val && $msg->$fun($val);
        }
        if(is_callable($this->handler))
        {
            $fun = $this->handler;
            $fun($msg,$notifiable,$cfg,$ios);
        }
        $msg->build();
        \Log::debug("xiaomi push msg $dvc",[$msg->getFields(),$msg->getJSONInfos()]);
        return $msg;
    }

    /**
     * 处理消息格式
     */
    public function setHandler(\Closure $fun)
    {
        $this->handler = new SerializableClosure($fun);
        return $this;
    }

    public function toMsg($notifiable = null)
    {
        return $this->message;
    }

    public function toArray($notifiable = null)
    {
        return $this->message;
    }

}
