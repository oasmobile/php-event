# Implementation Plan: oasis/event 2.0.0 PHP 8 Upgrade

`oasis/event` 2.0.0 release 的实现计划，所属 spec 目录：`.kiro/specs/release-2.0.0/`。

## Overview

将 `oasis/event` 从 PHP 5.6 / PHPUnit 5 升级到 PHP 8.2+ / PHPUnit 11，包括 Composer 配置、全面类型声明、拼写修正、Trait 安全约束、测试迁移与新增、SSOT 文档同步。按 design 中的依赖关系排序：Composer → 接口 / Event 并行 → Trait → PHPUnit 配置 → 测试迁移 → 新增测试 → 文档。

---

## Tasks

- [x] 1. Update Composer configuration
  - [x] 1.1 Update `composer.json` dependencies
    - Add `"php": ">=8.2"` to `require` section
    - Update `require-dev` from `"phpunit/phpunit": "^5.1"` to `"phpunit/phpunit": "^11.0"`
    - Verify no `version` field exists and no other runtime dependencies in `require`
    - Run `composer update` to regenerate `composer.lock`
    - Ref: Requirement 1, AC 1-4
  - [x] 1.2 Checkpoint: 执行 `composer validate` 确认 `composer.json` 格式正确；执行 `php -r "require 'vendor/autoload.php';"` 确认 autoload 正常

- [x] 2. Add type declarations to EventDispatcherInterface
  - [x] 2.1 Add PHP 8 type declarations to `src/EventDispatcherInterface.php`
    - Add parameter types and return types to all interface methods
    - `getParentEventDispatcher(): ?EventDispatcherInterface`
    - `setParentEventDispatcher(EventDispatcherInterface $parent): void`
    - `dispatch(Event|string $event, mixed $context = null): void`
    - `addEventListener(string $name, callable $listener, int $priority = 0): void`
    - `removeEventListener(string $name, callable $listener): void`
    - `removeAllEventListeners(string $name = ''): void`
    - `setDelegateDispatcher(?EventDispatcherInterface $delegate): void`
    - Ref: Requirement 3, AC 1-4
  - [x] 2.2 Checkpoint: 执行 `php -l src/EventDispatcherInterface.php` 确认无语法错误

- [x] 3. Add type declarations and fix spelling in Event class
  - [x] 3.1 Add PHP 8 type declarations and fix `Propogation` spelling in `src/Event.php`
    - Add typed property declarations for all class properties (including uninitialized `$target` and `$currentTarget` as `EventDispatcherInterface`)
    - Add `public` visibility to constructor, add parameter types and return types to constructor and all public methods
    - Rename properties: `$propogationStopped` → `$propagationStopped`, `$propogationStoppedImmediately` → `$propagationStoppedImmediately`
    - Rename methods: `stopPropogation()` → `stopPropagation()`, `stopImmediatePropogation()` → `stopImmediatePropagation()`, `isPropogationStopped()` → `isPropagationStopped()`, `isPropogationStoppedImmediately()` → `isPropagationStoppedImmediately()`
    - Use `void` return type for methods with no meaningful return value
    - Do NOT provide deprecated aliases for old method names
    - Ref: Requirement 2, AC 1-5; Requirement 5, AC 1-5, 7
  - [x] 3.2 Checkpoint: 执行 `php -l src/Event.php` 确认无语法错误

- [x] 4. Add type declarations, fix spelling, and add safety constraints to EventDispatcherTrait
  - [x] 4.1 Add PHP 8 type declarations, fix spelling, and add safety constraints in `src/EventDispatcherTrait.php`
    - Add typed property declarations: `$eventParent` as `?EventDispatcherInterface`, `$eventListeners` as `array` with phpdoc `@var array<string, array<int, array<int, callable>>>`, `$delegateDispatcher` as `?EventDispatcherInterface`
    - Add parameter types and return types to all public and protected methods, matching `EventDispatcherInterface` signatures
    - Add `@phpstan-require-implements EventDispatcherInterface` annotation to trait docblock
    - Add runtime `LogicException` check at the beginning of `dispatch()`: if `$this` is not an instance of `EventDispatcherInterface`, throw `\LogicException`
    - Update all internal references from `Propogation` to `Propagation` (method calls: `isPropogationStopped()` → `isPropagationStopped()`, `isPropogationStoppedImmediately()` → `isPropagationStoppedImmediately()`)
    - Ref: Requirement 4, AC 1-6; Requirement 5, AC 6
  - [x] 4.2 Checkpoint: 执行 `php -l src/EventDispatcherTrait.php` 确认无语法错误；执行 `composer install` 确认依赖正常；执行 `php -l src/Event.php src/EventDispatcherInterface.php src/EventDispatcherTrait.php` 确认三个源文件均无语法错误

- [x] 5. Update PHPUnit configuration
  - [x] 5.1 Update `phpunit.xml` to PHPUnit 11+ format
    - Change schema URL from `http://schema.phpunit.de/5.1/phpunit.xsd` to `https://schema.phpunit.de/11.0/phpunit.xsd`
    - Keep `bootstrap="vendor/autoload.php"`
    - Change test suite from `<file>ut/EventTest.php</file>` to `<directory>ut</directory>`
    - Ref: Requirement 6, AC 1-4
  - [x] 5.2 Checkpoint: 执行 `vendor/bin/phpunit --version` 确认 PHPUnit 11.x 可用

- [x] 6. Migrate existing tests to PHPUnit 11+ style
  - [x] 6.1 Migrate `ut/EventTest.php` to PHPUnit 11+ API
    - Replace `\PHPUnit_Framework_TestCase` with `PHPUnit\Framework\TestCase`
    - Replace `\PHPUnit_Framework_MockObject_MockObject` with `PHPUnit\Framework\MockObject\MockObject`
    - Replace `$this->getMockBuilder("stdClass")->setMethods([...])` with PHPUnit 11+ mock API (`$this->getMockBuilder(\stdClass::class)->addMethods([...])`)
    - Update `protected function setUp()` to `protected function setUp(): void`
    - Update all `Propogation` references to `Propagation` in test code (e.g. `stopPropogation()` → `stopPropagation()`, `stopImmediatePropogation()` → `stopImmediatePropagation()`)
    - Ref: Requirement 7, AC 1-4
  - [x] 6.2 Checkpoint: 执行 `vendor/bin/phpunit` 确认所有迁移后的测试通过，无错误、无 deprecation warning

- [x] 7. Add new test coverage — Example-based and Smoke tests
  - [x] 7.1 Add Event construction tests
    - Test all parameter combinations: name only, name + context, name + context + bubbles, name + context + bubbles + cancellable
    - Verify getters return correct values and `isCancelled()` returns false
    - Ref: Requirement 8, AC 1
  - [x] 7.2 Add cancel and preventDefault tests
    - Test `cancel()` on cancellable event: verify `isCancelled()` returns true
    - Test `cancel()` on non-cancellable event: verify `LogicException` is thrown
    - Test `preventDefault()` behaves identically to `cancel()`
    - Ref: Requirement 8, AC 2-4
  - [x] 7.3 Add context read/write tests
    - Test `getContext()` and `setContext()` for various values
    - Test `dispatch()` with context parameter sets context on event
    - Ref: Requirement 8, AC 5, 13
  - [x] 7.4 Add doesBubble tests
    - Test `doesBubble()` returns `true` for bubbling events and `false` for capturing events
    - Ref: Requirement 8, AC 6
  - [x] 7.5 Add bubbling mode target/currentTarget tests
    - Test that `getTarget()` returns the originating (child) dispatcher at every handler in the chain
    - Test that `getCurrentTarget()` returns the dispatcher currently handling the event, following child → parent order
    - Ref: Requirement 8, AC 7
  - [x] 7.6 Add capturing mode target/currentTarget and execution order tests
    - Test that `getTarget()` returns the originating (child) dispatcher
    - Test that `getCurrentTarget()` follows root → parent → child order (reversed chain)
    - Verify parent-to-child execution order for capturing events
    - Ref: Requirement 8, AC 8, 14
  - [x] 7.7 Add removeAllEventListeners tests
    - Test removing all listeners for a specific event name preserves other event listeners
    - Test removing all listeners with empty string removes all listeners for all events
    - Ref: Requirement 8, AC 9-10
  - [x] 7.8 Add listener priority ordering tests
    - Test that listeners with lower numeric priority execute first
    - Ref: Requirement 8, AC 11
  - [x] 7.9 Add delegate dispatcher test
    - Test that dispatch is forwarded to the delegate dispatcher
    - Ref: Requirement 8, AC 12
  - [x] 7.10 Add trait safety constraint test
    - Test that a class using `EventDispatcherTrait` without implementing `EventDispatcherInterface` throws `LogicException` on `dispatch()`
    - Ref: Requirement 4, AC 5
  - [x] 7.11 Checkpoint: 执行 `vendor/bin/phpunit` 确认所有测试（迁移 + 新增 example-based）通过，无错误、无 deprecation warning

- [x] 8. Verify PBT library compatibility and add property-based tests
  - [x] 8.1 Check PhpQuickCheck compatibility with PHP 8.2 / PHPUnit 11
    - Attempt `composer require --dev steffenfriedrich/php-quickcheck`
    - If compatible, use PhpQuickCheck for PBT; if not, fall back to `@dataProvider` + custom random generators (minimum 100 iterations per property)
    - Ref: Design Testing Strategy
  - [x] 8.2 Write property test for Event construction value integrity
    - **Property 1: Event 构造保持值完整性**
    - Generate random (name: non-empty string, context: mixed, bubbles: bool, cancellable: bool) and verify all getters return construction parameters, `isCancelled()` returns false
    - Validates: Requirement 2, AC 5; Requirement 8, AC 1
  - [x] 8.3 Write property test for context read/write consistency
    - **Property 2: Context 读写一致性**
    - Generate random mixed values, verify `setContext()` then `getContext()` returns identical value; verify `dispatch($event, $context)` sets context correctly
    - Validates: Requirement 8, AC 5, 13
  - [x] 8.4 Write property test for bubbling mode target/currentTarget correctness
    - **Property 3: 冒泡模式下 target/currentTarget 正确性**
    - Generate random chain depth 2-5, verify `getTarget()` always returns child dispatcher, `getCurrentTarget()` follows child → parent order
    - Validates: Requirement 8, AC 7
  - [x] 8.5 Write property test for capturing mode target/currentTarget and execution order
    - **Property 4: 捕获模式下 target/currentTarget 与执行顺序**
    - Generate random chain depth 2-5, verify `getTarget()` always returns child dispatcher, `getCurrentTarget()` follows root → child order (reversed)
    - Validates: Requirement 8, AC 8, 14
  - [x] 8.6 Write property test for removeAllEventListeners selective and global removal
    - **Property 5: removeAllEventListeners 选择性与全局移除**
    - Generate random event name sets and listeners, verify selective removal only affects specified name, global removal clears all
    - Validates: Requirement 8, AC 9-10
  - [x] 8.7 Write property test for listener priority ordering
    - **Property 6: 监听器优先级排序**
    - Generate random distinct integer priorities, verify execution order matches ascending priority (ksort)
    - Validates: Requirement 8, AC 11
  - [x] 8.8 Checkpoint: 执行 `vendor/bin/phpunit` 确认所有测试（迁移 + example-based + PBT）通过，无错误、无 deprecation warning

- [x] 9. Update SSOT documentation
  - [x] 9.1 Update `docs/state/architecture.md`
    - Change 语言 to "PHP（>=8.2）"
    - Change 测试 to "PHPUnit ^11.0"
    - Add "运行时依赖：`php >=8.2`（Composer `require`）" to 技术选型
    - Update 测试策略 section with PHPUnit version description
    - Ref: Requirement 9, AC 1
  - [x] 9.2 Update `docs/state/api.md`
    - Replace all `Propogation` references with `Propagation` in method names and descriptions
    - Add type signatures (parameter types + return types) to all public methods in Event, EventDispatcherInterface, and EventDispatcherTrait sections
    - Update `setDelegateDispatcher` parameter to `?EventDispatcherInterface`
    - Remove the historical note about `Propogation` being a legacy spelling
    - Ref: Requirement 9, AC 2-4
  - [x] 9.3 Checkpoint: 目视检查 `docs/state/architecture.md` 和 `docs/state/api.md` 与实现代码一致；执行 `vendor/bin/phpunit` 确认全量测试仍通过

- [x] 10. Manual testing
  - [x] 10.1 环境准备：确认 PHP >=8.2 已安装，`composer install` 成功，`vendor/bin/phpunit --version` 输出 PHPUnit 11.x
  - [x] 10.2 验证 Composer 约束生效：检查 `composer.json` 中 `require.php` 为 `>=8.2`，`require-dev.phpunit/phpunit` 为 `^11.0`，无 `version` 字段
  - [x] 10.3 验证类型声明完整性：使用 PHP Reflection API 检查 Event、EventDispatcherInterface、EventDispatcherTrait 的所有公开方法均有参数类型和返回类型声明
  - [x] 10.4 验证拼写修正：确认源代码中不存在 `Propogation` 拼写（`grep -r "Propogation" src/` 无结果）
  - [x] 10.5 验证 Trait 安全约束：创建一个不实现 `EventDispatcherInterface` 但使用 `EventDispatcherTrait` 的临时类，调用 `dispatch()` 确认抛出 `LogicException`
  - [x] 10.6 验证全量测试通过：执行 `vendor/bin/phpunit`，确认所有测试通过、无 deprecation warning
  - [x] 10.7 验证 SSOT 文档一致性：目视检查 `docs/state/architecture.md` 和 `docs/state/api.md` 与实现代码一致

- [x] 11. Code Review
  - 委托给 code-reviewer sub-agent 执行

---

## Execution Notes

- 按 `spec-execution.md` 规范执行所有 task
- 每个 top-level task 的 checkpoint 通过后进行一次 commit
- Task 2 和 Task 3 可并行执行（分别修改 `src/EventDispatcherInterface.php` 和 `src/Event.php`，无文件冲突）
- Task 4 依赖 Task 2 和 Task 3 完成（Trait 需匹配接口签名，且引用 Event 的修正后方法名）
- PBT 库选择策略（Task 8.1）：先尝试 PhpQuickCheck，不兼容则回退到 `@dataProvider` + 自定义随机生成器——来自 design CR Q1 决策
- 测试迁移（Task 6）和新增测试（Task 7-8）拆分为独立 task——来自 design CR Q2 决策
- SSOT 文档更新（Task 9）作为独立 task 在所有代码完成后统一执行——来自 design CR Q3 决策

---

## Socratic Review

**Q: tasks 是否完整覆盖了 design 中的所有实现项？有无遗漏的模块或接口？**

逐项核对 design 中的实现项：Composer 配置变更 → Task 1；EventDispatcherInterface 类型声明 → Task 2；Event 类型声明 + 拼写修正 → Task 3；EventDispatcherTrait 类型声明 + 拼写修正 + 安全约束 → Task 4；PHPUnit 配置变更 → Task 5；测试迁移 → Task 6；新增 example-based 测试 → Task 7；PBT 测试 → Task 8；SSOT 文档更新 → Task 9。全部覆盖，无遗漏。

**Q: task 之间的依赖顺序是否正确？是否存在隐含的前置依赖未体现在排序中？**

Task 1（Composer）无依赖，最先执行。Task 2（接口）和 Task 3（Event）可并行，已在 Execution Notes 中标注。Task 4（Trait）依赖 Task 2 和 3，排在其后。Task 5（PHPUnit 配置）依赖 Task 1（PHPUnit 版本）。Task 6（测试迁移）依赖 Task 1-5 全部完成。Task 7-8（新增测试）依赖 Task 6。Task 9（文档）依赖所有代码变更完成。顺序正确，无隐含依赖遗漏。

**Q: 每个 task 的粒度是否合适？是否有过粗或过细的 task？**

各 top-level task 按文件或功能维度拆分，每个 sub-task 聚焦单一文件或单一测试场景。Task 7 的 sub-task 较多（10 个测试场景 + 1 个 checkpoint），但每个场景独立且具体，粒度合适。无过粗或过细的 task。

**Q: checkpoint 的设置是否覆盖了关键阶段？**

每个 top-level task 末尾都有 checkpoint：Task 1（Composer 验证）、Task 2-4（语法检查）、Task 5（PHPUnit 版本确认）、Task 6（迁移测试通过）、Task 7（新增测试通过）、Task 8（PBT 测试通过）、Task 9（文档一致性 + 全量测试）。覆盖了所有关键阶段。

**Q: 标注为可并行的 sub-task 是否真的满足并行条件？**

Task 2 修改 `src/EventDispatcherInterface.php`，Task 3 修改 `src/Event.php`，不修改同一文件，且两者之间无调用依赖（接口不依赖 Event 的方法名变更，Event 不依赖接口的类型声明）。满足并行条件。

**Q: 手工测试是否覆盖了 requirements 中的关键用户场景？**

手工测试覆盖了：Composer 约束生效（Req 1）、类型声明完整性（Req 2-4）、拼写修正（Req 5）、Trait 安全约束（Req 4 AC 5）、全量测试通过（Req 7-8）、SSOT 文档一致性（Req 9）。覆盖了所有关键用户场景。


---

## Gatekeep Log

**校验时间**: 2025-07-15
**校验结果**: ⚠️ 已修正后通过

### 修正项

- [结构] Checkpoint（原 Task 5, 8, 11, 13）从独立 top-level task 改为各 top-level task 的最后一个 sub-task
- [结构] 补充了手工测试 top-level task（Task 10），覆盖 Composer 约束、类型声明完整性、拼写修正、Trait 安全约束、全量测试、SSOT 文档一致性
- [结构] 补充了 Code Review top-level task（Task 11），委托给 code-reviewer sub-agent 执行
- [结构] `## Notes` 重命名为 `## Execution Notes`，补充了对 `spec-execution.md` 的引用和 commit 时机说明，补充了并行策略和 design CR 决策的执行要点
- [结构] 补充了 `## Socratic Review` section，覆盖 design 覆盖度、依赖顺序、粒度、checkpoint、并行条件、手工测试
- [结构] 一级标题下方补充了文件定位说明和所属 spec 目录
- [结构] 各 section 之间补充了 `---` 分隔符
- [格式] Requirement 引用格式从 `_Requirements: X.X, X.X_` 统一为 `Ref: Requirement X, AC Y` 格式
- [格式] PBT sub-task（原 10.2-10.7）的 `[ ]*` optional 标记移除，改为 mandatory（所有 task 均为 mandatory）
- [内容] 原 top-level task 序号因 checkpoint 合并和新增 task 而重新编排（13 → 11 个 top-level task）

### 合规检查

- [x] 无 TBD / TODO / 待定 / 占位符
- [x] 无空 section 或不完整的列表
- [x] 内部引用一致（requirement 编号、design 中的模块名）
- [x] checkbox 语法正确（`- [ ]`）
- [x] 无 markdown 格式错误
- [x] `## Tasks` section 存在
- [x] 倒数第一个 top-level task 是 Code Review
- [x] 倒数第二个 top-level task 是手工测试
- [x] 自动化实现 task 排在手工测试和 Code Review 之前
- [x] 所有 task 使用 `- [ ]` checkbox 语法
- [x] top-level task 有序号（1-11），sub-task 有层级序号
- [x] 序号连续，无跳号
- [x] 每个实现类 sub-task 引用了具体的 requirements 条款
- [x] requirements.md 中的每条 requirement 至少被一个 task 引用（Req 1-9 全覆盖）
- [x] 引用的 requirement 编号和 AC 编号在 requirements.md 中确实存在
- [x] top-level task 按依赖关系排序
- [x] 无循环依赖
- [x] 并行条件成立（Task 2 和 Task 3 修改不同文件，无调用依赖）
- [x] checkpoint 作为每个 top-level task 的最后一个 sub-task
- [x] checkpoint 描述包含具体的验证命令
- [x] 每个 sub-task 足够具体，可在独立 session 中执行
- [x] 无过粗或过细的 task
- [x] 所有 task 均为 mandatory
- [x] 手工测试 top-level task 存在，覆盖关键用户场景
- [x] Code Review 是最后一个 top-level task，描述为委托给 code-reviewer sub-agent
- [x] `## Execution Notes` section 存在，包含 spec-execution.md 引用、commit 时机、并行策略、design CR 决策
- [x] `## Socratic Review` section 存在，覆盖充分
- [x] Design CR 三项决策（Q1: PBT 策略, Q2: 测试拆分, Q3: SSOT 时机）均在 tasks 编排中体现
- [x] Design 全覆盖（Req 1-9 对应 Task 1-9）
- [x] 每个 sub-task 可独立执行
- [x] 验收闭环完整（checkpoint + 手工测试 + code review）
- [x] 执行路径无歧义
