# Migration Guide: 1.x → 3.0

从 `oasis/event` 1.x 升级到 3.0 的迁移指南。3.0 经历了两个 major release（2.0、3.0），本文将所有 breaking change 合并说明。

---

## 环境要求

| 项目 | 1.x | 3.0 |
|------|-----|-----|
| PHP | 5.6+ | **>=8.2** |
| PHPUnit | ^5.1 | ^11.0 |
| Composer `require` | 无 PHP 约束 | `"php": ">=8.2"` |

升级前请确认运行环境满足 PHP 8.2+ 要求。

---

## Breaking Changes 一览

### 1. 方法拼写修正（2.0 引入）

1.x 中 `Propagation` 拼写为 `Propogation`，2.0 已修正。需全局搜索替换：

| 1.x 方法名 | 3.0 方法名 |
|------------|-----------|
| `stopPropogation()` | `stopPropagation()` |
| `stopImmediatePropogation()` | `stopImmediatePropagation()` |
| `isPropogationStopped()` | `isPropagationStopped()` |
| `isPropogationStoppedImmediately()` | `isPropagationStoppedImmediately()` |

**操作**：在项目中搜索 `Propogation`（注意大小写），替换为 `Propagation`。

### 2. 全面类型声明（2.0 引入）

所有公开方法均添加了参数类型和返回类型声明。如果你的代码存在以下情况，需要修正：

- 向方法传入了错误类型的参数（1.x 静默接受，3.0 抛出 `TypeError`）
- 子类覆写了 `Event` 的方法但未声明兼容的类型签名

### 3. Event 属性变为 `readonly`（3.0 引入）

`Event` 的 `$name`、`$bubbles`、`$cancellable` 属性添加了 `readonly` 修饰符。影响：

- 子类无法在构造后修改这三个属性
- 如果子类构造函数中在 `parent::__construct()` 之后重新赋值这些属性，将触发 `Error`

**操作**：检查 `Event` 子类，确保不在构造后修改 `$name`、`$bubbles`、`$cancellable`。

### 4. Constructor Promotion（3.0 引入）

`Event` 构造函数参数改为 promoted parameters：

```php
// 3.0
public function __construct(
    protected readonly string $name,
    protected mixed $context = null,
    protected readonly bool $bubbles = true,
    protected readonly bool $cancellable = true,
) {}
```

对于普通使用者（通过 `new Event(...)` 构造），**无需改动**。但如果子类通过 `$this->name` 等方式在构造函数中直接访问属性，行为不变；只是属性声明方式从显式声明移到了构造函数参数上。

### 5. `removeEventListener()` 比较逻辑变更（3.0 引入）

回调比较从 `is_string`/`is_array` 分支判断 + `==` 宽松比较，改为统一使用 `!==` 严格比较。

影响场景：如果你依赖宽松比较来匹配监听器（例如用 `==` 等价但非同一引用的数组回调），3.0 下可能无法正确移除。

**操作**：确保 `removeEventListener()` 传入的 `$listener` 与 `addEventListener()` 时传入的是**同一引用**。

### 6. `call_user_func` 替换为直接调用（3.0 引入）

`doDispatchEvent()` 中 `call_user_func($callback, $event)` 替换为 `$callback($event)`。对于标准 callable（闭包、函数名字符串、`[$obj, 'method']` 数组），行为一致。

如果你使用了非标准 callable 形式（如 `'ClassName::staticMethod'` 字符串），请验证其在直接调用语法下是否正常工作。

---

## 迁移步骤

1. **升级 PHP 到 8.2+**
2. **更新 Composer 依赖**
   ```bash
   composer require oasis/event:^3.0
   ```
3. **全局搜索替换拼写**
   - `stopPropogation` → `stopPropagation`
   - `stopImmediatePropogation` → `stopImmediatePropagation`
   - `isPropogationStopped` → `isPropagationStopped`
   - `isPropogationStoppedImmediately` → `isPropagationStoppedImmediately`
4. **修复类型不匹配**：运行静态分析（PHPStan / Psalm）或测试套件，修复 `TypeError`
5. **检查 Event 子类**：确保不在构造后修改 `readonly` 属性
6. **检查 `removeEventListener` 调用**：确保传入同一回调引用
7. **运行测试**，确认无回归

---

## 已移除内容

| 内容 | 说明 |
|------|------|
| `Created by PhpStorm` 注释块 | 3.0 移除，无功能影响 |
| Event 构造函数 `@param` PHPDoc | 3.0 移除，类型信息已由声明提供 |
| `EventDispatcherInterface::dispatch()` PHPDoc | 3.0 移除 |

这些移除不影响运行时行为。

---

## 新增能力

- `EventDispatcherTrait` 添加 `@phpstan-require-implements EventDispatcherInterface` 注解，PHPStan 可在编译期检测未实现接口的误用
- `dispatch()` 运行时安全断言：使用 trait 但未实现接口时抛出 `LogicException`，而非静默失败
