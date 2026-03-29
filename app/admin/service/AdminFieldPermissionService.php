<?php
declare(strict_types=1);

namespace app\admin\service;

use app\admin\model\AdminButtonPermission;
use app\admin\model\AdminFieldPermission;
use app\admin\model\AdminUser;
use think\facade\Cache;
use think\facade\Db;

class AdminFieldPermissionService extends AdminBaseService
{
    /**
     * 字段权限优先级（值越大越严格）
     * hidden > disabled > readonly > 默认(editable)
     */
    private const FIELD_PRIORITY = [
        'hidden'   => 3,
        'disabled' => 2,
        'readonly' => 1,
    ];

    /**
     * 标准按钮列表
     */
    public const STANDARD_BUTTONS = ['add', 'delete', 'enable', 'disable', 'export', 'import'];

    /**
     * 按钮中文名称映射
     */
    public const BUTTON_LABELS = [
        'add'     => '添加',
        'delete'  => '删除',
        'enable'  => '启用',
        'disable' => '禁用',
        'export'  => '导出',
        'import'  => '导入',
    ];

    /**
     * 获取用户在某控制器+动作下的字段权限（多角色合并，最严格原则）
     *
     * @param AdminUser $user
     * @param string $controller 如 "AdminUser"
     * @param string $action     如 "index"/"add"/"edit"
     * @return array ['fieldName' => 'hidden|readonly|disabled']，空数组表示全部放行
     */
    public function getUserFieldPermission(AdminUser $user, string $controller, string $action): array
    {
        $roleIds = $this->getUserRoleIds($user);
        if (empty($roleIds)) {
            return [];
        }

        // Check which roleIds are already cached; fetch uncached ones in a single query
        $sets        = [];
        $missingIds  = [];
        foreach ($roleIds as $roleId) {
            $cacheKey = 'field_perm_' . md5($roleId . '|' . $controller . '|' . $action);
            $cached   = Cache::get($cacheKey);
            if ($cached !== null) {
                $sets[$roleId] = $cached;
            } else {
                $missingIds[] = $roleId;
            }
        }

        if (!empty($missingIds)) {
            $records = AdminFieldPermission::where('role_id', 'in', $missingIds)
                ->where('controller', $controller)
                ->where('action', $action)
                ->where('delete_time', 0)
                ->column('field_config', 'role_id');

            foreach ($missingIds as $roleId) {
                $perm     = isset($records[$roleId]) ? (json_decode($records[$roleId], true) ?: []) : [];
                $cacheKey = 'field_perm_' . md5($roleId . '|' . $controller . '|' . $action);
                Cache::set($cacheKey, $perm, 300);
                $sets[$roleId] = $perm;
            }
        }

        return $this->mergeFieldPermissions(array_values($sets));
    }

    /**
     * 获取用户在某控制器的按钮权限（任一角色禁用则禁用）
     *
     * @param AdminUser $user
     * @param string $controller 如 "AdminUser"
     * @return array ['add'=>1,'delete'=>0,...] 空数组表示全部放行
     */
    public function getUserButtonPermission(AdminUser $user, string $controller): array
    {
        $roleIds = $this->getUserRoleIds($user);
        if (empty($roleIds)) {
            return [];
        }

        // Check which roleIds are already cached; fetch uncached ones in a single query
        $perRole    = [];
        $missingIds = [];
        foreach ($roleIds as $roleId) {
            $cacheKey = 'btn_perm_' . md5($roleId . '|' . $controller);
            $cached   = Cache::get($cacheKey);
            if ($cached !== null) {
                $perRole[$roleId] = $cached;
            } else {
                $missingIds[] = $roleId;
            }
        }

        if (!empty($missingIds)) {
            $records = AdminButtonPermission::where('role_id', 'in', $missingIds)
                ->where('controller', $controller)
                ->where('delete_time', 0)
                ->column('button_config', 'role_id');

            foreach ($missingIds as $roleId) {
                $config   = isset($records[$roleId]) ? (json_decode($records[$roleId], true) ?: []) : [];
                $cacheKey = 'btn_perm_' . md5($roleId . '|' . $controller);
                Cache::set($cacheKey, $config, 300);
                $perRole[$roleId] = $config;
            }
        }

        $merged = [];
        foreach ($perRole as $config) {
            foreach ($config as $btn => $val) {
                // 任一角色禁用（0）则最终禁用
                $merged[$btn] = isset($merged[$btn])
                    ? min($merged[$btn], (int)$val)
                    : (int)$val;
            }
        }
        return $merged;
    }

    /**
     * 读取某角色在某控制器+动作的字段配置（管理后台用）
     */
    public function getFieldConfig(int $roleId, string $controller, string $action): array
    {
        $record = AdminFieldPermission::where([
            'role_id'    => $roleId,
            'controller' => $controller,
            'action'     => $action,
            'delete_time' => 0,
        ])->findOrEmpty();

        return $record->isEmpty() ? [] : ($record->field_config ?: []);
    }

    /**
     * 读取某角色在某控制器的按钮配置（管理后台用）
     */
    public function getButtonConfig(int $roleId, string $controller): array
    {
        $record = AdminButtonPermission::where([
            'role_id'    => $roleId,
            'controller' => $controller,
            'delete_time' => 0,
        ])->findOrEmpty();

        return $record->isEmpty() ? [] : ($record->button_config ?: []);
    }

    /**
     * 保存字段权限配置（upsert）
     */
    public function saveFieldPermission(int $roleId, string $controller, string $action, array $config): bool
    {
        // 过滤掉值为空/默认的配置，只保留有实际限制的
        $config = array_filter($config, fn($v) => !empty($v) && $v !== 'default');

        $record = AdminFieldPermission::where([
            'role_id'    => $roleId,
            'controller' => $controller,
            'action'     => $action,
            'delete_time' => 0,
        ])->findOrEmpty();

        if ($record->isEmpty()) {
            AdminFieldPermission::create([
                'role_id'      => $roleId,
                'controller'   => $controller,
                'action'       => $action,
                'field_config' => $config,
            ]);
        } else {
            $record->save(['field_config' => $config]);
        }

        $this->clearFieldCache($roleId, $controller, $action);
        return true;
    }

    /**
     * 保存按钮权限配置（upsert）
     */
    public function saveButtonPermission(int $roleId, string $controller, array $config): bool
    {
        $record = AdminButtonPermission::where([
            'role_id'    => $roleId,
            'controller' => $controller,
            'delete_time' => 0,
        ])->findOrEmpty();

        if ($record->isEmpty()) {
            AdminButtonPermission::create([
                'role_id'       => $roleId,
                'controller'    => $controller,
                'button_config' => $config,
            ]);
        } else {
            $record->save(['button_config' => $config]);
        }

        $this->clearButtonCache($roleId, $controller);
        return true;
    }

    /**
     * 自动获取控制器对应的数据库字段列表（SHOW COLUMNS）
     * 排除系统字段 id/create_time/update_time/delete_time
     */
    public function getControllerFields(string $controller): array
    {
        if (!preg_match('/^Admin[A-Za-z]+$/', $controller)) {
            return [];
        }
        // Verify the controller class actually exists to prevent processing of arbitrary names
        $fqcn = 'app\\admin\\controller\\' . $controller . 'Controller';
        if (!class_exists($fqcn)) {
            return [];
        }
        $table = $this->controllerToTable($controller);
        try {
            $columns = Db::query("SHOW COLUMNS FROM `{$table}`");
            $excludes = ['id', 'create_time', 'update_time', 'delete_time'];
            return array_values(array_filter(
                array_column($columns, 'Field'),
                fn($f) => !in_array($f, $excludes)
            ));
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * 扫描 admin controller 目录，获取所有可配置的控制器列表
     */
    public function getControllerList(): array
    {
        $path  = app_path() . 'admin' . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR;
        $files = glob($path . 'Admin*Controller.php') ?: [];
        $list  = [];
        $skip  = ['AdminBaseController', 'AdminFieldPermissionController'];
        foreach ($files as $file) {
            $name = str_replace('Controller.php', '', basename($file));
            if (!in_array($name, $skip)) {
                $list[] = $name;
            }
        }
        sort($list);
        return $list;
    }

    /**
     * 控制器名 → 数据库表名
     * AdminUser → admin_user
     * AdminLog  → admin_log
     */
    public function controllerToTable(string $controller): string
    {
        // 去掉末尾可能的 "Controller"
        $name = preg_replace('/Controller$/', '', $controller);
        // PascalCase → snake_case
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
    }

    /**
     * 多角色字段权限合并：最严格原则
     * hidden(3) > disabled(2) > readonly(1) > 默认(0)
     */
    private function mergeFieldPermissions(array $sets): array
    {
        $result = [];
        foreach ($sets as $perms) {
            foreach ($perms as $field => $state) {
                $curPriority = self::FIELD_PRIORITY[$result[$field] ?? ''] ?? 0;
                $newPriority = self::FIELD_PRIORITY[$state] ?? 0;
                if ($newPriority > $curPriority) {
                    $result[$field] = $state;
                }
            }
        }
        return $result;
    }

    /**
     * 提取用户的角色 ID 数组
     */
    private function getUserRoleIds(AdminUser $user): array
    {
        $role = $user->getData('role') ?? $user->role ?? [];
        if (is_string($role)) {
            $role = array_filter(explode(',', $role));
        }
        return array_map('intval', (array)$role);
    }

    /**
     * 清除字段权限缓存（单条）
     */
    private function clearFieldCache(int $roleId, string $controller, string $action): void
    {
        Cache::delete('field_perm_' . md5($roleId . '|' . $controller . '|' . $action));
    }

    /**
     * 清除按钮权限缓存（单条）
     */
    private function clearButtonCache(int $roleId, string $controller): void
    {
        Cache::delete('btn_perm_' . md5($roleId . '|' . $controller));
    }
}
