<?php

namespace app\common\enum;

enum OrderStatus: int
{
    case PENDING = 0;
    case PAID = 1;
    case SHIPPED = 2;
    case COMPLETED = 3;
    case CANCELED = 4;

    public function label(): string
    {
        return match ($this)
        {
            self::PENDING => '待支付',
            self::PAID => '已支付',
            self::SHIPPED => '已发货',
            self::COMPLETED => '已完成',
            self::CANCELED => '已取消',
        };
    }
}
