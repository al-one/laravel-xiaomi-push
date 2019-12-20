<?php

namespace Alone\LaravelXiaomiPush;

use xmpush;

trait WithXiaomiNotification
{

    /**
     * 小米推送
     * @return xmpush\Builder|xmpush\IOSBuilder
     */
    public function toXiaomiPush($notifiable,$msg,$cfg = [])
    {
        $ios = $msg instanceof xmpush\IOSBuilder;
        $payload = method_exists($this,'payload') ? $this->payload() : null;
        if($msg instanceof xmpush\IOSBuilder)
        {
            if(method_exists($this,'body'))
            {
                $msg->body($this->body());
            }
            if($payload)
            {
                $msg->extra('payload',json_encode($payload));
            }
        }
        elseif($msg instanceof xmpush\Builder)
        {
            $msg->passThrough(0);
            if($payload)
            {
                $msg->payload(json_encode($payload));
            }
            $msg->extra(xmpush\Builder::notifyEffect,1/*打开APP*/);
            $msg->extra(xmpush\Builder::notifyForeground,0);
        }
        foreach(['title','description'] as $fun)
        {
            $val = method_exists($this,$fun) ? $this->$fun() : null;
            $val && $msg->$fun($val);
        }
        if(isset($this->handler) && is_callable($this->handler))
        {
            $fun = $this->handler;
            $fun($msg,$notifiable,$cfg,$ios);
        }
        return $msg;
    }

}
