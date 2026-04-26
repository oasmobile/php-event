# Changelog v3.0.0

本文件记录 v3.0.0 release 的变更内容。

---

## 包含的 Feature

### PHP 8.1/8.2 语法现代化（release-3.0）

将 `oasis/event` 代码库全面升级为 PHP 8.1/8.2 现代语法。作为 major release，包含 breaking change，不提供向后兼容。

**Breaking Changes**

- Event 类 `$name`、`$bubbles`、`$cancellable` 属性添加 `readonly` 修饰符，子类无法在构造后修改这些属性
- Event 类构造函数参数改为 constructor promotion，属性声明方式变化
- `removeEventListener()` 回调比较逻辑从 `is_string`/`is_array` 分支判断 + `==` 改为统一 `!==` 严格比较

**Changed**

- Event 类：构造函数参数 `$name`、`$context`、`$bubbles`、`$cancellable` 改为 promoted parameters
- Event 类：`$name`、`$bubbles`、`$cancellable` 添加 `readonly` 修饰符（`protected readonly`）
- EventDispatcherTrait：`doDispatchEvent()` 中 `call_user_func($callback, $event)` 替换为 `$callback($event)` 直接调用
- EventDispatcherTrait：`removeEventListener()` 中移除 `$comp` 闭包，改为 `$callback !== $listener` 严格比较

**Removed**

- 移除所有文件的 `Created by PhpStorm` 注释块
- 移除 Event 构造函数的 `@param` PHPDoc block
- 移除 EventDispatcherInterface `dispatch()` 方法的 PHPDoc block

---

## 修复的 Issue

无。

---

## 工程变更

- SSOT 文档（`docs/state/architecture.md`、`docs/state/api.md`）同步更新，反映语法现代化后的系统状态

---

## 测试覆盖

- 62 tests, 3319 assertions
- 全部通过，无 deprecation warning
- 新增 `ut/EventStructureTest.php`：Reflection API 结构验证（constructor promotion、readonly）
- 新增 2 个 PBT property：回调移除正确性（严格比较）、非目标监听器不受移除影响
