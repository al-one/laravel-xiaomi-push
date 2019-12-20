<?php

namespace Alone\LaravelXiaomiPush;

use Illuminate\Support;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Opis\Closure\SerializableClosure;

class XiaomiNotification extends Notification implements ShouldQueue
{
    use Queueable,
        WithXiaomiNotification;

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
