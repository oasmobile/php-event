# oasis/event

PHP 事件分发辅助库，提供 Event 对象、EventDispatcher 接口与 Trait 实现。

## 技术栈

- **语言**：PHP
- **包管理**：Composer
- **命名空间**：`Oasis\Mlib\Event\`
- **自动加载**：PSR-4（`src/`）
- **测试框架**：PHPUnit ^5.1（dev 依赖）
- **许可证**：MIT

## 构建 / 测试命令

```bash
# 安装依赖
composer install

# 运行全量测试
vendor/bin/phpunit

# 运行单个测试文件
vendor/bin/phpunit ut/EventTest.php
```

## 目录结构

| 路径 | 说明 |
|------|------|
| `src/` | 源代码（PSR-4 根） |
| `ut/` | 单元测试 |
| `phpunit.xml` | PHPUnit 配置 |
| `composer.json` | Composer 包定义 |

## 版本号位置

- `composer.json` → `version` 字段（当前未显式声明，由 Packagist / Git tag 决定）

## 敏感文件

- `composer.lock`（依赖锁定，不含密钥但影响可复现性）
- 无 `.env` 或凭据文件
