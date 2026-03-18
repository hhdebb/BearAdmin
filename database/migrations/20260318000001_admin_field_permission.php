<?php
/**
 * 字段权限配置表
 */

use think\migration\Migrator;

class AdminFieldPermission extends Migrator
{
    public function change(): void
    {
        $table = $this->table('admin_field_permission', [
            'comment'   => '字段权限配置',
            'engine'    => 'InnoDB',
            'encoding'  => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]);
        $table
            ->addColumn('role_id', 'integer', ['signed' => false, 'limit' => 10, 'default' => 0, 'comment' => '角色ID'])
            ->addColumn('controller', 'string', ['limit' => 100, 'default' => '', 'comment' => '控制器名,如 AdminUser'])
            ->addColumn('action', 'string', ['limit' => 50, 'default' => '', 'comment' => '动作名 index/add/edit'])
            ->addColumn('field_config', 'text', ['null' => true, 'comment' => '字段权限JSON,如 {"username":"hidden","mobile":"readonly"}'])
            ->addColumn('create_time', 'integer', ['signed' => false, 'limit' => 10, 'default' => 0, 'comment' => '创建时间'])
            ->addColumn('update_time', 'integer', ['signed' => false, 'limit' => 10, 'default' => 0, 'comment' => '更新时间'])
            ->addColumn('delete_time', 'integer', ['signed' => false, 'limit' => 10, 'default' => 0, 'comment' => '删除时间'])
            ->addIndex(['role_id', 'controller', 'action'], ['unique' => true, 'name' => 'uniq_role_ctrl_action'])
            ->create();
    }
}
