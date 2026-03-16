# BearAdmin

基于 **ThinkPHP 6.0** + **AdminLTE 3.2** 构建的开箱即用后台管理系统，内置 RBAC 权限、操作日志、文件管理、RESTful API 等核心模块。

[![PHP](https://img.shields.io/badge/PHP-%3E%3D7.4-blue)](https://www.php.net/)
[![ThinkPHP](https://img.shields.io/badge/ThinkPHP-6.0-green)](https://www.thinkphp.cn/)
[![AdminLTE](https://img.shields.io/badge/AdminLTE-3.2-orange)](https://adminlte.io/)
[![License](https://img.shields.io/badge/License-Apache%202.0-blue)](LICENSE)

[开发文档](https://www.kancloud.cn/codebear/admin_tp6) ｜ [在线 DEMO](https://demo.bearadmin.com/) ｜ [DEMO 源码](https://github.com/yupoxiong/bearadmin-demo) ｜ [TP5.1 版本](https://github.com/yupoxiong/BearAdmin/tree/thinkphp5.1) ｜ [TP5.0 版本](https://github.com/yupoxiong/BearAdmin/tree/thinkphp5.0)

---

## 功能特性

### 权限与用户管理
- **RBAC 角色权限**：基于角色的菜单/操作权限控制，支持细粒度节点授权
- **管理员管理**：多账号、角色分配、头像、状态管理
- **单设备登录**：防止账号同时多处登录（可配置）
- **前台用户管理**：独立的前台用户表，支持用户等级体系

### 安全与审计
- **操作日志**：自动记录所有写操作，保存请求参数与响应结果
- **CSRF 防护**：所有状态变更请求均做 Token 校验
- **密码自动加密**：模型层自动 Hash，无需手动处理
- **验证码支持**：登录页可配置图形验证码 / 极验

### 菜单与导航
- **可视化菜单管理**：树形结构，支持无限层级
- **动态侧边栏**：根据当前角色权限自动渲染

### 内容与配置
- **设置管理**：分组的键值存储，支持文本、图片、富文本等多种字段类型
- **用户等级**：可扩展的等级/会员体系

### 文件管理
- **文件上传**：本地存储，支持图片/文件分类管理
- **Excel 导入导出**：集成 PHPSpreadsheet 与 XLSXWriter

### 代码生成
- **CRUD 生成器**：根据数据库表一键生成控制器、视图、表单字段
- **多种字段类型**：文本、下拉、日期时间、手机号、身份证、多选、时间范围等

### RESTful API
- **独立 API 模块**：与后台管理分离，适合移动端 / SPA 接入
- **JWT 认证**：Token 签发与验证，支持过期时间配置
- **请求限流**：防重复提交与频率控制
- **标准分页**：统一的 `page` / `limit` 参数

---

## 技术栈

| 层次 | 技术 |
|------|------|
| 后端框架 | ThinkPHP 6.0 |
| ORM | Think-ORM 2.0 |
| 前端 UI | AdminLTE 3.2（Bootstrap 4） |
| 数据库 | MySQL 5.7+ |
| 认证（后台） | Session |
| 认证（API） | JWT |
| 模板引擎 | ThinkPHP 原生模板 |
| 文件处理 | PHPSpreadsheet / XLSXWriter |
| 数据库迁移 | Think-Migration |

---

## 环境要求

| 依赖 | 版本要求 |
|------|---------|
| PHP | >= 7.4（枚举特性需 >= 8.1） |
| MySQL | >= 5.7 |
| PHP 扩展 | `openssl` `json` `curl` `mbstring` `pdo_mysql` |
| Composer | >= 2.0 |
| Web 服务器 | Apache（含 `mod_rewrite`）或 Nginx |

---

## 安装步骤

### 1. 克隆项目

```bash
# GitHub
git clone https://github.com/yupoxiong/BearAdmin.git

# 码云（国内镜像）
git clone https://gitee.com/yupoxiong/BearAdmin.git

cd BearAdmin
```

### 2. 安装 PHP 依赖

```bash
composer install
```

### 3. 创建数据库

**必须使用 `utf8mb4` 编码**，否则迁移会失败：

```sql
CREATE DATABASE `bearadmin`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

### 4. 配置环境变量

```bash
cp .env.example .env
```

编辑 `.env`，填写数据库连接信息：

```ini
[APP]
DEBUG = false
TIMEZONE = Asia/Shanghai

[DATABASE]
TYPE     = mysql
HOSTNAME = 127.0.0.1
DATABASE = bearadmin
USERNAME = root
PASSWORD = your_password
HOSTPORT = 3306
CHARSET  = utf8mb4
DEBUG    = false
```

> 完整配置项说明见 `.env.example`。

### 5. 运行数据库迁移

```bash
php think migrate:run
```

迁移完成后，命令行会输出随机生成的初始密码，**请立即复制保存**：

```
develop_admin 密码：xxxxxxxx
super_admin   密码：xxxxxxxx
```

> 为防止弱密码导致后台被入侵，初始密码每次迁移随机生成，不可预测。

### 6. 配置 Web 服务器

将 **`public/`** 目录设为 Web 根目录，并开启 URL 重写。

**Nginx 示例：**

```nginx
location / {
    if (!-e $request_filename) {
        rewrite ^(.*)$ /index.php?s=$1 last;
    }
}
```

**Apache：** `public/.htaccess` 已包含重写规则，确保已启用 `mod_rewrite`。

详细配置参考 [ThinkPHP 6.0 URL 访问文档](https://www.kancloud.cn/manual/thinkphp6_0/1037488)。

### 7. 访问后台

打开浏览器访问 `http://your-domain/admin`：

| 账号 | 说明 |
|------|------|
| `develop_admin` | 开发管理员（拥有全部权限，用于开发调试） |
| `super_admin` | 超级管理员（生产环境使用） |

密码见第 5 步迁移命令的输出。

---

## 常用命令

```bash
# 数据库迁移
php think migrate:run          # 执行待运行的迁移
php think migrate:rollback     # 回滚最近一次迁移

# 账号管理
php think reset:admin_password # 交互式重置管理员密码

# 密钥生成
php think generate:jwt_key     # 生成 JWT 密钥（API 模块使用）
php think generate:app_key     # 生成应用加密密钥

# 环境初始化
php think init:env             # 初始化 .env 配置
php think service:discover     # 发现并注册服务
php think vendor:publish       # 发布扩展包资源
```

---

## 目录结构

```
BearAdmin/
├── app/
│   ├── admin/          # 后台管理模块（访问路径 /admin）
│   ├── api/            # RESTful API 模块（访问路径 /api）
│   ├── index/          # 前台模块（访问路径 /）
│   └── common/         # 公共代码（模型、服务、枚举、自定义标签库）
├── config/             # 全局配置
├── database/
│   └── migrations/     # 数据库迁移文件
├── extend/             # 扩展工具库（JWT、辅助函数等）
├── public/             # Web 根目录（入口文件、静态资源）
├── route/              # 路由定义
└── think               # CLI 入口（php think <command>）
```

---

## API 模块

API 模块使用 JWT 认证，适合移动端或前后端分离场景。

**基础路径：** `/api`

**认证流程：**
1. `POST /api/auth/login` — 提交账号密码，返回 `token`
2. 后续请求在 Header 中携带：`Authorization: Bearer <token>`

**通用请求参数：**

| 参数 | 类型 | 说明 |
|------|------|------|
| `page` | int | 页码，默认 `1` |
| `limit` | int | 每页条数，默认 `10`，最大 `100` |
| `id` | int/string | 单个 ID 或逗号分隔的多个 ID |

**在 `.env` 中配置 API 参数：**

```ini
[API]
JWT_SECRET   = your_jwt_secret
TOKEN_EXPIRE = 86400        # Token 有效期（秒）
```

---

## 开发指南

### 新增后台 CRUD 模块

1. 创建数据库迁移：`database/migrations/`
2. 创建模型（继承 `AdminBaseModel`）：`app/admin/model/`
3. 创建业务服务（继承 `AdminBaseService`）：`app/admin/service/`
4. 创建验证器（继承 `AdminBaseValidate`）：`app/admin/validate/`
5. 创建控制器（继承 `AdminBaseController`）：`app/admin/controller/`
6. 创建视图目录：`app/admin/view/{控制器名}/`（index、add、edit 模板）
7. 在后台菜单管理页面添加对应菜单项

也可使用内置的**代码生成器**（`/admin/generate`）根据数据库表自动生成上述文件。

### 代码规范

- 命名空间：`app\{模块}\{层次}`，如 `app\admin\controller`
- 类名：PascalCase，如 `AdminUserController`
- 方法/属性：camelCase，如 `checkLogin()`
- 数据库表名：snake_case，如 `admin_user`

---

## 致谢

本项目基于众多优秀开源项目构建，包括但不限于：

- [ThinkPHP](https://github.com/top-think/framework)
- [AdminLTE](https://github.com/ColorlibHQ/AdminLTE)
- [PHPSpreadsheet](https://github.com/PHPOffice/PhpSpreadsheet)
- [jQuery](https://jquery.com/)
- [Bootstrap](https://getbootstrap.com/)

如有署名遗漏，欢迎联系：`i#yupoxiong.com`（将 `#` 替换为 `@`）

如果你需要 Laravel 版本的后台管理系统，推荐 [LaravelAdmin](https://github.com/yuxingfei/LaravelAdmin)。

交流 QQ 群：[480018279](//shang.qq.com/wpa/qunwpa?idkey=2e8674491df685dab9f634773b72ce8ed7df033aed7cbf194cda95dd4ad45737)

---

## License

本项目基于 [Apache 2.0 协议](LICENSE) 开源。
