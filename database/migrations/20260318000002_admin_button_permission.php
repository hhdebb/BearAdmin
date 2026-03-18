<?php
/**
 * 按钮权限配置表
 */

use think\migration\Migrator;

class AdminButtonPermission extends Migrator
{
    public function change(): void
    {
        $table = $this->table('admin_button_permission', [
            'comment'   => '按钮权限配置',
            'engine'    => 'InnoDB',
            'encoding'  => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]);
        $table
            ->addColumn('role_id', 'integer', ['signed' => false, 'limit' => 10, 'default' => 0, 'comment' => '角色ID'])
            ->addColumn('controller', 'string', ['limit' => 100, 'default' => '', 'comment' => '控制器名,如 AdminUser'])
            ->addColumn('button_config', 'text', ['null' => true, 'comment' => '按钮权限JSON,如 {"add":1,"delete":0,"enable":1}'])
            ->addColumn('create_time', 'integer', ['signed' => false, 'limit' => 10, 'default' => 0, 'comment' => '创建时间'])
            ->addColumn('update_time', 'integer', ['signed' => false, 'limit' => 10, 'default' => 0, 'comment' => '更新时间'])
            ->addColumn('delete_time', 'integer', ['signed' => false, 'limit' => 10, 'default' => 0, 'comment' => '删除时间'])
            ->addIndex(['role_id', 'controller'], ['unique' => true, 'name' => 'uniq_role_ctrl'])
            ->create();
    }
}
