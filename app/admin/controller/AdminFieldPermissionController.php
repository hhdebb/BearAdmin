<?php
/**
 * 字段与按钮权限管理控制器
 */

declare(strict_types=1);

namespace app\admin\controller;

use Exception;
use think\Request;
use think\response\Json;
use app\admin\model\AdminRole;
use app\admin\model\AdminFieldPermission;
use app\admin\model\AdminButtonPermission;
use app\admin\service\AdminFieldPermissionService;

class AdminFieldPermissionController extends AdminBaseController
{
    protected AdminFieldPermissionService $service;

    public function __construct()
    {
        $this->service = new AdminFieldPermissionService();
        parent::__construct();
    }

    /**
     * 权限管理列表页
     * 展示角色列表 + 控制器列表，提供配置入口
     *
     * @param Request $request
     * @return string
     * @throws Exception
     */
    public function index(Request $request): string
    {
        $roles       = (new AdminRole)->where('delete_time', 0)->where('status', 1)->select();
        $controllers = $this->service->getControllerList();

        // 当前选中的角色（用于跨页保持选中状态）
        $current_role_id = (int)$request->get('role_id', 0);

        $this->assign([
            'roles'           => $roles,
            'controllers'     => $controllers,
            'current_role_id' => $current_role_id,
        ]);
        return $this->fetch();
    }

    /**
     * 字段/按钮权限配置页
     * 表格UI：行=字段，列=列表/新增/编辑
     *
     * @param Request $request
     * @return string
     * @throws Exception
     */
    public function config(Request $request): string
    {
        $role_id    = (int)$request->get('role_id', 0);
        $controller = (string)$request->get('controller', '');

        if ($role_id <= 0 || empty($controller)) {
            return $this->fetch('error/404');
        }

        $role   = (new AdminRole)->findOrEmpty($role_id);
        $fields = $this->service->getControllerFields($controller);

        // 读取三个场景已有的字段配置
        $fieldPerms = [
            'index' => $this->service->getFieldConfig($role_id, $controller, 'index'),
            'add'   => $this->service->getFieldConfig($role_id, $controller, 'add'),
            'edit'  => $this->service->getFieldConfig($role_id, $controller, 'edit'),
        ];

        // 读取按钮配置
        $btnPerms = $this->service->getButtonConfig($role_id, $controller);

        $this->assign([
            'role'            => $role,
            'controller'      => $controller,
            'table_name'      => $this->service->controllerToTable($controller),
            'fields'          => $fields,
            'field_perms'     => $fieldPerms,
            'btn_perms'       => $btnPerms,
            'standard_buttons' => AdminFieldPermissionService::STANDARD_BUTTONS,
            'button_labels'   => AdminFieldPermissionService::BUTTON_LABELS,
            'field_states'    => [
                ''         => '默认（可编辑）',
                'readonly' => '只读',
                'disabled' => '禁用',
                'hidden'   => '隐藏',
            ],
        ]);
        return $this->fetch();
    }

    /**
     * 保存字段权限配置
     * POST: role_id, controller, action, config[field]=state
     *
     * @param Request $request
     * @return Json
     */
    public function saveField(Request $request): Json
    {
        $role_id    = (int)$request->post('role_id', 0);
        $controller = (string)$request->post('controller', '');
        $action     = (string)$request->post('action', '');
        $config     = $request->post('config/a', []);

        if ($role_id <= 0 || empty($controller) || !in_array($action, ['index', 'add', 'edit'], true)) {
            return admin_error('参数错误');
        }

        try {
            $this->service->saveFieldPermission($role_id, $controller, $action, (array)$config);
            return admin_success('保存成功');
        } catch (\Exception $e) {
            return admin_error('保存失败：' . $e->getMessage());
        }
    }

    /**
     * 保存按钮权限配置
     * POST: role_id, controller, config[button]=0|1
     *
     * @param Request $request
     * @return Json
     */
    public function saveButton(Request $request): Json
    {
        $role_id    = (int)$request->post('role_id', 0);
        $controller = (string)$request->post('controller', '');
        $config     = $request->post('config/a', []);

        if ($role_id <= 0 || empty($controller)) {
            return admin_error('参数错误');
        }

        // 标准按钮列表，未出现在 config 中的表示选中（允许），出现且值为 0 表示隐藏
        $finalConfig = [];
        foreach (AdminFieldPermissionService::STANDARD_BUTTONS as $btn) {
            $finalConfig[$btn] = isset($config[$btn]) ? (int)$config[$btn] : 1;
        }

        try {
            $this->service->saveButtonPermission($role_id, $controller, $finalConfig);
            return admin_success('保存成功');
        } catch (\Exception $e) {
            return admin_error('保存失败：' . $e->getMessage());
        }
    }

    /**
     * AJAX 接口：获取控制器对应的数据库字段列表
     *
     * @param Request $request
     * @return Json
     */
    public function getFields(Request $request): Json
    {
        $controller = (string)$request->get('controller', '');
        if (empty($controller)) {
            return admin_error('参数错误');
        }
        $fields = $this->service->getControllerFields($controller);
        return admin_success('ok', $fields);
    }
}
