# Changelog v2.0.0

本文件记录 v2.0.0 release 的变更内容。

---

## 包含的 Feature

### PHP 8 Upgrade（release-2.0.0）

将 `oasis/event` 从 PHP 5.6 / PHPUnit 5 升级到 PHP 8.2+ / PHPUnit 11。作为 major release，包含 breaking change，不提供向后兼容。

**Breaking Changes**

- 方法重命名：`stopPropogation()` → `stopPropagation()`、`stopImmediatePropogation()` → `stopImmediatePropagation()`、`isPropogationStopped()` → `isPropagationStopped()`、`isPropogationStoppedImmediately()` → `isPropagationStoppedImmediately()`
- 所有公开方法添加 PHP 8 类型声明（参数类型 + 返回类型），传入错误类型会收到 `TypeError`
- Composer `require` 新增 `php >=8.2`，不再支持 PHP 8.2 以下版本

**Added**

- Event、EventDispatcherInterface、EventDispatcherTrait 全面添加 PHP 8 类型声明（typed properties、参数类型、返回类型）
- EventDispatcherTrait 添加 `@phpstan-require-implements EventDispatcherInterface` 注解
- EventDispatcherTrait `dispatch()` 添加运行时安全断言：未实现 `EventDispatcherInterface` 时抛出 `LogicException`

**Changed**

- Composer `require`：新增 `"php": ">=8.2"`
- Composer `require-dev`：`phpunit/phpunit` 从 `^5.1` 升级到 `^11.0`
- `phpunit.xml` schema 升级到 PHPUnit 11.0
- 测试套件从指定文件改为扫描 `ut/` 目录

**Tests**

- 现有测试迁移到 PHPUnit 11+ API（`TestCase` 基类、mock API、`setUp(): void`）
- 新增 Event 构造、cancel/preventDefault、context 读写、doesBubble、target/currentTarget（冒泡/捕获）、removeAllEventListeners、优先级排序、delegate dispatcher、trait 安全约束等测试
- 新增 property-based 测试（6 个 property，每个 100+ 次迭代）

---

## 修复的 Issue

无。

---

## 工程变更

- PHPUnit 配置升级到 11.0 schema
- 新增 `giorgiosironi/eris` dev 依赖（PBT 测试库）
- SSOT 文档（`docs/state/architecture.md`、`docs/state/api.md`）同步更新

---

## 测试覆盖

- 36 tests, 2626 assertions
- 全部通过，无 deprecation warning
