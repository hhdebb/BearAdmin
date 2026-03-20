<?php
declare(strict_types=1);

namespace app\admin\model;

class AdminButtonPermission extends AdminBaseModel
{
    protected $name = 'admin_button_permission';

    /**
     * button_config JSON 读取器
     */
    public function getButtonConfigAttr($value): array
    {
        if (empty($value)) {
            return [];
        }
        return json_decode($value, true) ?: [];
    }

    /**
     * button_config JSON 写入器
     */
    public function setButtonConfigAttr($value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        return (string)$value;
    }
}
