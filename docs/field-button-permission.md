# 字段级别与按钮级别权限控制

> 版本：v1.0 · 日期：2026-03-18

---

## 一、功能概述

本功能在 BearAdmin 原有 URL/菜单节点级权限（RBAC）的基础上，新增了**字段级**和**按钮级**的细粒度访问控制，允许管理员按角色配置：

- 列表页中哪些**字段列**可见
- 新增/编辑表单中每个字段的**可见性与可编辑性**（新增与编辑可独立配置）
- 列表页中哪些**操作按钮**可见（添加、删除、启用、禁用、导出、导入）

---

## 二、设计决策

| 维度 | 决策 | 说明 |
|------|------|------|
| 权限维度 | 角色级别 | 同角色所有用户共享同一套配置，与现有 RBAC 体系保持一致 |
| 字段状态 | 4 种 | 默认（可见可编辑）/ 只读 / 禁用 / 隐藏 |
| 作用场景 | 3 种 | 列表页（index）/ 新增表单（add）/ 编辑表单（edit），同一字段在不同场景可独立配置 |
| 字段来源 | 自动解析 | `SHOW COLUMNS` 自动读取控制器对应数据表的字段，无需手动维护 |
| 默认行为 | 白名单 | 未配置的字段/按钮默认全部放行（显示、可编辑） |
| 超管豁免 | 是 | `id = 1`（develop_admin）完全跳过字段/按钮权限校验 |
| 多角色合并 | 最严格原则 | 用户拥有多个角色时，取最严格的状态：隐藏 > 禁用 > 只读 > 默认 |
| 前端执行 | 服务端渲染 | Controller 注入模板变量，无闪烁风险，无法通过前端绕过 |
| 管理界面 | 独立菜单项 | 表格式 UI（行=字段，列=场景），支持角色/控制器选择 |

---

## 三、数据库结构

### 表 `admin_field_permission` — 字段权限配置

| 字段 | 类型 | 说明 |
|------|------|------|
| `id` | int unsigned | 主键 |
| `role_id` | int unsigned | 关联 `admin_role.id` |
| `controller` | varchar(100) | 控制器名，如 `AdminUser` |
| `action` | varchar(50) | 动作名：`index` / `add` / `edit` |
| `field_config` | text | JSON，如 `{"username":"hidden","mobile":"readonly"}` |
| `create_time` | int unsigned | 创建时间（时间戳） |
| `update_time` | int unsigned | 更新时间（时间戳） |
| `delete_time` | int unsigned | 软删除时间戳 |

- 唯一索引：`(role_id, controller, action)`
- 每条记录存储一个（角色 × 控制器 × 场景）下所有字段的限制配置

### 表 `admin_button_permission` — 按钮权限配置

| 字段 | 类型 | 说明 |
|------|------|------|
| `id` | int unsigned | 主键 |
| `role_id` | int unsigned | 关联 `admin_role.id` |
| `controller` | varchar(100) | 控制器名，如 `AdminUser` |
| `button_config` | text | JSON，如 `{"add":1,"delete":0,"enable":1,"disable":1,"export":0,"import":0}` |
| `create_time` | int unsigned | 创建时间 |
| `update_time` | int unsigned | 更新时间 |
| `delete_time` | int unsigned | 软删除时间戳 |

- 唯一索引：`(role_id, controller)`
- 按钮值：`1` = 显示，`0` = 隐藏

---

## 四、文件结构

### 新增文件

```
database/migrations/
├── 20260318000001_admin_field_permission.php   # 字段权限表迁移
└── 20260318000002_admin_button_permission.php   # 按钮权限表迁移

app/admin/
├── model/
│   ├── AdminFieldPermission.php                # 字段权限模型
│   └── AdminButtonPermission.php               # 按钮权限模型
├── service/
│   └── AdminFieldPermissionService.php         # 核心业务逻辑
├── controller/
│   └── AdminFieldPermissionController.php      # 管理后台控制器
└── view/admin_field_permission/
    ├── index.html                              # 角色×控制器选择页
    └── config.html                             # 字段/按钮权限配置页
```

### 修改文件

```
app/admin/
├── controller/
│   └── AdminBaseController.php                 # fetch() 注入权限变量
└── view/admin_user/
    ├── index.html                              # 示范：列/按钮权限控制
    └── add.html                                # 示范：表单字段权限控制
```

---

## 五、核心模块说明

### 5.1 AdminFieldPermissionService

**文件**：`app/admin/service/AdminFieldPermissionService.php`

| 方法 | 说明 |
|------|------|
| `getUserFieldPermission($user, $controller, $action)` | 获取用户字段权限（多角色合并，5分钟缓存） |
| `getUserButtonPermission($user, $controller)` | 获取用户按钮权限（多角色合并，5分钟缓存） |
| `getFieldConfig($roleId, $controller, $action)` | 读取指定角色的字段配置（管理后台用） |
| `getButtonConfig($roleId, $controller)` | 读取指定角色的按钮配置（管理后台用） |
| `saveFieldPermission($roleId, $controller, $action, $config)` | 保存字段权限（upsert） |
| `saveButtonPermission($roleId, $controller, $config)` | 保存按钮权限（upsert） |
| `getControllerFields($controller)` | 自动解析控制器对应数据表字段（SHOW COLUMNS） |
| `getControllerList()` | 扫描目录获取所有可配置控制器列表 |
| `controllerToTable($controller)` | 控制器名 → 表名（`AdminUser` → `admin_user`） |

**多角色合并规则**（字段权限）：
```
优先级：hidden(3) > disabled(2) > readonly(1) > 默认(0)

示例：
  角色A：username = readonly
  角色B：username = hidden
  合并结果：username = hidden  ← 取最严格
```

**多角色合并规则**（按钮权限）：
```
任一角色禁用（0）则最终隐藏

示例：
  角色A：delete = 1（显示）
  角色B：delete = 0（隐藏）
  合并结果：delete = 0  ← 取 min()
```

### 5.2 AdminBaseController 注入逻辑

**文件**：`app/admin/controller/AdminBaseController.php`

在 `fetch()` 方法渲染模板前自动注入：

```php
// 超管（id=1）和登录页面跳过，其余自动注入
if (isset($this->user) && $this->user->id !== 1 && 'admin/auth/login' !== $this->url) {
    $permService = new AdminFieldPermissionService();
    $ctrlName    = $this->parseControllerName(); // "admin/admin_user/index" → "AdminUser"
    $action      = request()->action(true);       // "index" / "add" / "edit"
    $this->assign('_field_perm', $permService->getUserFieldPermission($this->user, $ctrlName, $action));
    $this->assign('_btn_perm',   $permService->getUserButtonPermission($this->user, $ctrlName));
} else {
    $this->assign('_field_perm', []);
    $this->assign('_btn_perm',   []);
}
```

所有继承 `AdminBaseController` 的控制器，其模板均可使用 `$_field_perm` 和 `$_btn_perm` 变量，**无需任何额外调用**。

### 5.3 AdminFieldPermissionController

**文件**：`app/admin/controller/AdminFieldPermissionController.php`

| 路由 | 方法 | 说明 |
|------|------|------|
| `GET  /admin/admin_field_permission/index` | `index()` | 角色列表 + 控制器列表 |
| `GET  /admin/admin_field_permission/config` | `config()` | 字段/按钮权限配置页（参数：`role_id`, `controller`） |
| `POST /admin/admin_field_permission/save_field` | `saveField()` | 保存字段权限 |
| `POST /admin/admin_field_permission/save_button` | `saveButton()` | 保存按钮权限 |
| `GET  /admin/admin_field_permission/get_fields` | `getFields()` | 获取控制器字段列表（AJAX） |

---

## 六、模板使用规范

所有需要接入权限控制的视图，遵循以下标准写法：

### 6.1 列表页 — 字段列控制

```html
<!-- 列头 -->
{if empty($_field_perm['username']) || $_field_perm['username'] !== 'hidden'}
<th>账号</th>
{/if}

<!-- 列数据 -->
{if empty($_field_perm['username']) || $_field_perm['username'] !== 'hidden'}
<td>{$item.username}</td>
{/if}
```

> 列表页的字段状态只有「显示」和「隐藏」两种，只读/禁用在列表场景无意义。

### 6.2 列表页 — 按钮控制

```html
{if empty($_btn_perm) || !isset($_btn_perm['add']) || $_btn_perm['add'] == 1}
<a href="{:url('add')}" class="btn btn-primary btn-sm">
    <i class="fa fa-plus"></i> 添加
</a>
{/if}

{if empty($_btn_perm) || !isset($_btn_perm['delete']) || $_btn_perm['delete'] == 1}
<button class="btn btn-danger btn-sm AjaxButton" data-id="checked" data-url="{:url('del')}">
    <i class="fa fa-trash"></i> 删除
</button>
{/if}
```

### 6.3 表单页 — 字段四态控制

```html
{if empty($_field_perm['username']) || $_field_perm['username'] !== 'hidden'}
<div class="form-group row">
    <label class="col-sm-2 col-form-label">账号</label>
    <div class="col-sm-10 col-md-4 formInputDiv">

        {if !empty($_field_perm['username']) && $_field_perm['username'] === 'readonly'}
        {{-- 只读：显示纯文本 --}}
        <p class="form-control-plaintext">{$data.username|default=''}</p>

        {elseif !empty($_field_perm['username']) && $_field_perm['username'] === 'disabled'}
        {{-- 禁用：显示置灰 input，需额外 hidden input 保证值提交 --}}
        <input type="text" class="form-control" name="username"
               value="{$data.username|default=''}" disabled>
        <input type="hidden" name="username" value="{$data.username|default=''}">

        {else}
        {{-- 默认：正常可编辑 --}}
        <input type="text" class="form-control" name="username"
               value="{$data.username|default=''}" placeholder="请输入账号">
        {/if}

    </div>
</div>
{/if}
```

> **新增与编辑使用同一模板（`add.html`）**，通过 `{if isset($data)}` 区分渲染差异。`_field_perm` 由 BaseController 根据当前请求的 `action`（`add` 或 `edit`）自动切换对应配置，无需在模板中判断。

### 6.4 字段状态速查

| `_field_perm['字段名']` 值 | 列表页 | 表单页渲染 | 值是否提交 |
|--------------------------|--------|-----------|-----------|
| 空（未配置） | 显示列 | 正常 input | ✓ |
| `'readonly'` | 显示列 | 纯文本 `<p>` | ✗（不在表单内） |
| `'disabled'` | 显示列 | 置灰 input + hidden | ✓（靠 hidden） |
| `'hidden'` | **不渲染** | **不渲染** | ✗ |

---

## 七、后台管理使用方法

### 7.1 运行迁移

```bash
php think migrate:run
```

执行后自动创建 `admin_field_permission` 和 `admin_button_permission` 两张表。

### 7.2 添加菜单节点

在后台「菜单管理」中添加：

| 字段 | 值 |
|------|---|
| 名称 | 字段权限管理 |
| URL | `admin/admin_field_permission/index` |
| 父菜单 | 权限管理（或按需放置） |
| 日志记录方式 | GET |

同时在对应角色的授权中勾选该菜单节点，并将以下节点也加入权限列表（建议设为隐藏菜单节点）：

| URL | 说明 |
|-----|------|
| `admin/admin_field_permission/config` | 配置页 |
| `admin/admin_field_permission/save_field` | 保存字段权限接口 |
| `admin/admin_field_permission/save_button` | 保存按钮权限接口 |
| `admin/admin_field_permission/get_fields` | 获取字段列表接口 |

### 7.3 配置权限

1. 进入「字段权限管理」
2. 左侧点击选择**角色**
3. 右侧列表中找到目标**控制器**，点击「字段权限」
4. 在表格中为每个字段在三个场景下分别选择状态
5. 在按钮权限区域勾选/取消该角色可见的按钮
6. 分别点击「保存字段权限」和「保存按钮权限」

---

## 八、为其他模块接入权限控制

`_field_perm` 和 `_btn_perm` 已自动注入到所有 admin 视图，**只需改造模板**：

**改造清单（以某模块为例）：**

```
1. 列表页（index.html）：
   - 表头 <th> 用 {if empty($_field_perm['字段名']) || ...} 包裹
   - 表格行 <td> 用相同条件包裹
   - 顶部操作按钮区用 {if empty($_btn_perm) || ...} 包裹
   - 行内操作按钮（删除/启用/禁用）用相同条件包裹

2. 表单页（add.html，新增+编辑复用）：
   - 每个 form-group 外层用 {if ... !== 'hidden'} 包裹
   - 内部用 {if readonly} / {elseif disabled} / {else} 处理三态
   - disabled 字段需额外加 <input type="hidden"> 保证值提交
```

参考实现见：`app/admin/view/admin_user/index.html` 和 `app/admin/view/admin_user/add.html`。

---

## 九、注意事项

### 缓存说明

权限查询结果缓存 **5 分钟**（TTL=300s），驱动与项目 `.env` 中 `CACHE.DRIVER` 一致（file 或 redis）。

修改权限配置后，缓存会在下一次保存时主动清除对应条目。若需立即生效，可临时将 TTL 改为 0，或重启缓存服务。

> 多角色场景下，缓存 key 基于用户角色组合的 MD5，无法批量精确清除。生产环境建议使用 Redis 并采用 Tag 机制管理缓存。

### 数据表名约定

Service 通过命名规则自动推导：`AdminUser` → `admin_user`，`AdminOrderDetail` → `admin_order_detail`。

若控制器与表名不符合 PascalCase → snake_case 规则（如存在缩写），需在 `AdminFieldPermissionService::controllerToTable()` 中添加例外映射：

```php
private array $tableMap = [
    'AdminSMS' => 'admin_sms',  // 特殊情况手动映射
];
```

### disabled 字段的值提交

HTML 规范规定 `disabled` 的 input **不会**随表单提交。若业务上需要该字段值提交（如编辑时保留原值），模板中必须配套一个同名的 `hidden` input：

```html
<input type="text" name="mobile" value="{$data.mobile}" disabled>
<input type="hidden" name="mobile" value="{$data.mobile}">  <!-- 保证提交 -->
```

### 超管账号

`id = 1` 的 `develop_admin` 用户完全豁免字段/按钮权限校验，`_field_perm` 和 `_btn_perm` 均为空数组，等价于全部放行。此行为与现有菜单权限豁免逻辑保持一致（见 `AdminAuthTrait::checkAuth()`）。

### CSRF Token

`saveField` 和 `saveButton` 接口为 POST 请求。若系统开启了 CSRF 校验（`admin.safe.check_token = 1`），且这两个 action 名在校验列表中，则 AJAX 请求需在 data 中携带 `__token__`：

```js
data: {
    __token__: $('meta[name="csrf-token"]').attr('content'),
    // ...
}
```

---

## 十、扩展建议

| 功能 | 说明 |
|------|------|
| **详情页支持** | 为 `detail`/`view` action 增加场景，控制详情页字段可见性 |
| **行级权限** | 在现有列级基础上，增加数据行的过滤（如只能看自己创建的数据） |
| **字段标签显示** | 配置页字段名旁显示中文 label（需维护字段注释映射） |
| **批量复制配置** | 将某角色某控制器的配置一键复制到另一角色 |
| **导入导出** | 支持 JSON 格式的权限配置导入/导出，便于多环境同步 |
| **Redis Tag 缓存** | 使用 Redis Tag 管理缓存，支持按角色或控制器批量失效 |
