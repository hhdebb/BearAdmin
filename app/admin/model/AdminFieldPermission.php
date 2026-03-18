<?php
declare(strict_types=1);

namespace app\admin\model;

class AdminFieldPermission extends AdminBaseModel
{
    protected $name = 'admin_field_permission';

    /**
     * field_config JSON 读取器
     */
    public function getFieldConfigAttr($value): array
    {
        if (empty($value)) {
            return [];
        }
        return json_decode($value, true) ?: [];
    }

    /**
     * field_config JSON 写入器
     */
    public function setFieldConfigAttr($value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        return (string)$value;
    }
}
