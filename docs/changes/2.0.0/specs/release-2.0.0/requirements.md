# Requirements Document

`oasis/event` 2.0.0 release 的需求定义文档，所属 spec 目录：`.kiro/specs/release-2.0.0/`。

---

## Introduction

`oasis/event` 库的 2.0.0 大版本升级。本次升级将库从 PHP 5.6 时代迁移到 PHP 8.2+ 生态，包括：添加 PHP 版本约束、全面引入类型声明、修正历史拼写错误、升级测试框架并补充测试覆盖、更新工程配置与文档。作为 major release，允许 breaking change，不提供向后兼容。

**不涉及的内容（Non-scope）**：

- 不在 `composer.json` 中声明 `version` 字段（继续由 Git tag 决定）
- 不保留旧拼写方法名作为 deprecated alias
- 不改变库的功能行为和架构设计
- 不添加新的运行时依赖
- 不提供从 1.x 到 2.0 的迁移路径或兼容层

## Glossary

- **Library**：`oasis/event` Composer 包，即本项目整体
- **Event**：`Oasis\Mlib\Event\Event` 类，事件值对象
- **EventDispatcherInterface**：`Oasis\Mlib\Event\EventDispatcherInterface`，分发器接口
- **EventDispatcherTrait**：`Oasis\Mlib\Event\EventDispatcherTrait`，分发器默认实现（trait）
- **Composer_Config**：项目根目录的 `composer.json` 文件
- **PHPUnit_Config**：项目根目录的 `phpunit.xml` 文件
- **Test_Suite**：`ut/` 目录下的 PHPUnit 测试集合
- **State_Docs**：`docs/state/` 目录下的 SSOT 文档（`architecture.md`、`api.md`）

---

## Requirements

### Requirement 1: Composer PHP 版本约束

**User Story:** 作为库使用者，我希望 Composer 配置声明 PHP 版本约束，以便不兼容的 PHP 环境在安装时被拒绝。

#### Acceptance Criteria

1. THE Composer_Config SHALL contain `"php": ">=8.2"` in the `require` section.
2. THE Composer_Config SHALL contain `"phpunit/phpunit": "^11.0"` in the `require-dev` section.
3. THE Composer_Config SHALL NOT contain a `version` field.
4. THE Composer_Config SHALL NOT contain any runtime dependencies other than `php` in the `require` section.

---

### Requirement 2: Event 类类型声明

**User Story:** 作为库使用者，我希望 Event 类具有完整的 PHP 8 类型声明，以便获得静态分析支持和运行时类型安全。

#### Acceptance Criteria

1. THE Event SHALL declare parameter types and return types on all public methods using PHP 8 type syntax (including `mixed`, `bool`, `string`, union types, `void`, `never` as appropriate).
2. THE Event SHALL declare parameter types and return types on the constructor.
3. THE Event SHALL declare typed property declarations for all class properties.
4. WHEN a public method has no meaningful return value, THE Event SHALL declare `void` as the return type.
5. THE Event SHALL preserve the existing method signatures' semantic behavior after adding type declarations.

---

### Requirement 3: EventDispatcherInterface 类型声明

**User Story:** 作为库使用者，我希望 EventDispatcherInterface 具有完整的 PHP 8 类型声明，以便实现类在编译时获得类型检查。

#### Acceptance Criteria

1. THE EventDispatcherInterface SHALL declare parameter types and return types on all methods using PHP 8 type syntax.
2. WHEN a method accepts either an Event object or a string, THE EventDispatcherInterface SHALL use a union type `Event|string` for that parameter.
3. WHEN a method returns the interface type or null, THE EventDispatcherInterface SHALL use a nullable type `?EventDispatcherInterface` for the return type.
4. THE EventDispatcherInterface SHALL preserve the existing method contracts' semantic behavior after adding type declarations.

---

### Requirement 4: EventDispatcherTrait 类型声明

**User Story:** 作为库使用者，我希望 EventDispatcherTrait 具有完整的 PHP 8 类型声明，以便 trait 使用者获得类型安全的默认实现。

#### Acceptance Criteria

1. THE EventDispatcherTrait SHALL declare parameter types and return types on all public and protected methods using PHP 8 type syntax.
2. THE EventDispatcherTrait SHALL declare typed property declarations for all trait properties.
3. THE EventDispatcherTrait SHALL match the type signatures declared in EventDispatcherInterface for all interface methods.
4. THE EventDispatcherTrait SHALL include a `@phpstan-require-implements EventDispatcherInterface` annotation to enforce static analysis constraints on trait users.
5. WHEN a class uses EventDispatcherTrait without implementing EventDispatcherInterface, THE EventDispatcherTrait SHALL throw a `\LogicException` at runtime during dispatch.
6. THE EventDispatcherTrait SHALL preserve the existing method behavior after adding type declarations.

---

### Requirement 5: 修正 Propagation 拼写

**User Story:** 作为库使用者，我希望所有方法名和属性名中的拼写错误 `Propogation` 被修正为 `Propagation`，以便 API 遵循标准英文拼写。

#### Acceptance Criteria

1. THE Event SHALL rename `stopPropogation()` to `stopPropagation()`.
2. THE Event SHALL rename `stopImmediatePropogation()` to `stopImmediatePropagation()`.
3. THE Event SHALL rename `isPropogationStopped()` to `isPropagationStopped()`.
4. THE Event SHALL rename `isPropogationStoppedImmediately()` to `isPropagationStoppedImmediately()`.
5. THE Event SHALL ensure all internal state related to propagation control uses the corrected `Propagation` spelling.
6. THE EventDispatcherTrait SHALL update all references from `Propogation` to `Propagation` in method calls.
7. THE Library SHALL NOT provide deprecated aliases or backward-compatible wrappers for the old misspelled method names.

---

### Requirement 6: PHPUnit 配置升级

**User Story:** 作为开发者，我希望 PHPUnit 配置兼容 PHPUnit 11+，以便测试套件运行时不产生弃用警告或错误。

#### Acceptance Criteria

1. THE PHPUnit_Config SHALL use the PHPUnit 11+ XML schema (`https://schema.phpunit.de/11.0/phpunit.xsd`).
2. THE PHPUnit_Config SHALL retain `vendor/autoload.php` as the bootstrap file.
3. THE PHPUnit_Config SHALL define a test suite that includes the `ut/` directory.
4. THE PHPUnit_Config SHALL be valid according to the PHPUnit 11 schema.

---

### Requirement 7: 测试迁移到 PHPUnit 11+ 风格

**User Story:** 作为开发者，我希望现有测试使用 PHPUnit 11+ API，以便测试套件与升级后的测试框架兼容。

#### Acceptance Criteria

1. THE Test_Suite SHALL use `PHPUnit\Framework\TestCase` as the base class (replacing `\PHPUnit_Framework_TestCase`).
2. THE Test_Suite SHALL use PHPUnit 11+ mock API (`$this->createMock()` or `$this->getMockBuilder()` with current API) replacing any deprecated mock patterns.
3. THE Test_Suite SHALL update all references from `Propogation` to `Propagation` in test code to match the renamed API.
4. THE Test_Suite SHALL pass without errors or deprecation warnings under PHPUnit 11.

---

### Requirement 8: 补充测试覆盖

**User Story:** 作为开发者，我希望对当前未覆盖的场景有全面的测试覆盖，以便在后续开发中捕获回归问题。

#### Acceptance Criteria

1. THE Test_Suite SHALL include tests for Event construction with all parameter combinations (name only, name + context, name + context + bubbles, name + context + bubbles + cancellable).
2. THE Test_Suite SHALL include tests for `Event::cancel()` on a cancellable event verifying `isCancelled()` returns true.
3. THE Test_Suite SHALL include tests for `Event::cancel()` on a non-cancellable event verifying a `LogicException` is thrown.
4. THE Test_Suite SHALL include tests for `Event::preventDefault()` verifying it behaves identically to `Event::cancel()`.
5. THE Test_Suite SHALL include tests for `Event::getContext()` and `Event::setContext()` verifying context read and write.
6. THE Test_Suite SHALL include tests for `Event::doesBubble()` returning the correct value for both bubbling and capturing events.
7. THE Test_Suite SHALL include tests for `Event::getTarget()` and `Event::getCurrentTarget()` in bubbling mode, verifying that `getTarget()` returns the originating (child) dispatcher and `getCurrentTarget()` returns the dispatcher currently handling the event as it propagates from child to parent.
8. THE Test_Suite SHALL include tests for `Event::getTarget()` and `Event::getCurrentTarget()` in capturing mode (`bubbles = false`), verifying that `getTarget()` returns the originating (child) dispatcher and `getCurrentTarget()` returns the dispatcher currently handling the event as it propagates from parent to child.
9. THE Test_Suite SHALL include tests for `removeAllEventListeners()` verifying all listeners for a specific event name are removed.
10. THE Test_Suite SHALL include tests for `removeAllEventListeners()` with an empty string verifying all listeners for all events are removed.
11. THE Test_Suite SHALL include tests for listener priority ordering verifying lower numeric priority executes first.
12. THE Test_Suite SHALL include tests for delegate dispatcher verifying that dispatch is forwarded to the delegate.
13. THE Test_Suite SHALL include tests for `dispatch()` with a context parameter verifying the context is set on the event.
14. THE Test_Suite SHALL include tests for capturing (non-bubbling) event dispatch verifying parent-to-child execution order.

---

### Requirement 9: 更新 SSOT 文档

**User Story:** 作为开发者，我希望 state 文档反映升级后的系统状态，以便 SSOT 在发布后保持准确。

#### Acceptance Criteria

1. WHEN all code changes are complete, THE State_Docs SHALL update `docs/state/architecture.md` to reflect PHP >=8.2, PHPUnit ^11.0, and the `php` runtime requirement in Composer.
2. WHEN all code changes are complete, THE State_Docs SHALL update `docs/state/api.md` to replace all `Propogation` references with `Propagation` in method names and descriptions.
3. WHEN all code changes are complete, THE State_Docs SHALL update `docs/state/api.md` to document the type signatures on all public methods.
4. THE State_Docs SHALL remove the historical note about `Propogation` being a legacy spelling.

---

## Socratic Review

**Q: 每条 requirement 是否都在描述外部可观察的行为？是否混入了实现细节？**

Requirement 1-4 描述的是 Composer 配置和 PHP 类型声明——对于一个 library 项目，这些都是公开 API 的一部分，属于外部可观察行为。Requirement 5 描述方法重命名，同样是 API 变更。Requirement 6-9 描述配置、测试和文档更新。整体上各 requirement 聚焦于外部可观察的行为和交付物，未混入不必要的实现细节。

原 Requirement 5 AC 5 曾指定内部 `protected` 属性的具体重命名（`$propogationStopped` → `$propagationStopped`），这属于内部实现细节，已修正为外部可观察的行为描述。

**Q: 是否有遗漏的场景？**

Requirement 8 的测试覆盖清单较为全面，覆盖了 Event 构造、取消、传播控制、分发链、委托、优先级等场景。一个潜在的遗漏是：`dispatch()` 传入字符串时自动包装为 Event 的行为——但这已在现有测试 `testDispatchString` 中覆盖，且 Requirement 7 AC 3 要求迁移现有测试，因此不算遗漏。

另一个边界场景是对同一事件重复添加相同 listener 的行为，以及 `removeEventListener` 对不存在的 listener 的处理。这些属于现有行为的边界条件，但本次升级的 Non-scope 明确"不改变库的功能行为"，因此不在本次 requirements 范围内。

**Q: 各 requirement 之间是否存在矛盾或重叠？**

Requirement 2-4（类型声明）和 Requirement 5（拼写修正）都涉及修改方法签名，但关注点不同：前者添加类型，后者修正命名。两者不矛盾，且在实现时自然合并。Requirement 7 AC 3 和 Requirement 5 AC 6 都涉及 `Propagation` 拼写更新，但作用域不同（测试代码 vs 源代码），不构成重叠。

**Q: 与 goal 的 scope / non-goals 是否一致？**

goal.md 列出 5 项目标和 4 项 non-goals。Requirements 1-9 完整覆盖了所有目标。Non-goals 已在 Introduction 的 Non-scope 中体现。Goal CR 的 4 个决策（全面类型声明、不保留旧名、不声明 version、迁移并补充测试）均已反映在对应的 requirement 中。一致。

**Q: scope 边界是否清晰？**

本次升级的边界清晰：仅涉及类型声明、拼写修正、配置升级、测试迁移和文档更新。不涉及功能变更、新依赖、兼容层。唯一可能的模糊地带是 Requirement 2 AC 1 中"including `mixed`, `bool`, `string`, union types, `void`, `never` as appropriate"——具体每个方法使用哪种类型属于 design 决策，requirements 只需要求"全面添加类型声明"即可。但考虑到 goal CR Q1 已明确选择"全面添加，利用 PHP 8 类型系统"，这里的枚举更多是示例性质，不构成歧义。

---

## Gatekeep Log

**校验时间**: 2025-07-15
**校验结果**: ⚠️ 已修正后通过

### 修正项

- [结构] 一级标题下方补充了文件定位说明和所属 spec 目录
- [结构] Introduction 补充了 Non-scope 段落，明确列出不涉及的内容（来源于 goal.md Non-Goals）
- [结构] Introduction 与 Glossary 之间补充了 `---` 分隔符
- [结构] 补充了 `## Socratic Review` section
- [语体] 全部 9 条 requirement 的 User Story 从英文改为中文（`作为 <角色>，我希望 <能力>，以便 <价值>`）
- [内容] Requirement 5 AC 5 原文指定了内部 `protected` 属性的具体重命名（`$propogationStopped` → `$propagationStopped`），属于内部实现细节，已改写为外部可观察的行为描述

### 合规检查

- [x] 无 TBD / TODO / 待定 / 占位符
- [x] 无空 section 或不完整的列表
- [x] 内部引用一致（术语表术语在 AC 中使用，requirement 编号连续）
- [x] 无 markdown 格式错误
- [x] 一级标题存在且附带定位说明
- [x] Introduction 存在，描述了 feature 范围
- [x] Introduction 明确了不涉及的内容（Non-scope）
- [x] Glossary 存在且非空，格式正确
- [x] Requirements section 存在且包含 9 条 requirement
- [x] 各 section 之间使用 `---` 分隔
- [x] Glossary 中的术语在 AC 中被实际使用（无孤立术语）
- [x] AC 中使用的领域概念在 Glossary 中有定义
- [x] User Story 使用中文行文
- [x] AC 使用 `THE <Subject> SHALL ...` / `WHEN ... THEN ...` 语体
- [x] AC 编号连续，无跳号
- [x] AC 聚焦外部可观察行为，未混入不必要的实现细节
- [x] Goal CR 的 4 项决策均已体现在对应 requirement 中
- [x] Socratic Review 覆盖了行为边界、场景遗漏、矛盾检查、goal 一致性、scope 清晰度

### Clarification Round

**状态**: ✅ 已完成

**Q1:** Requirement 2-4 要求"全面添加 PHP 8 类型声明"，但 `EventDispatcherTrait::dispatch()` 方法内部使用了 `$this` 作为 `EventDispatcherInterface` 传递（如 `$event->setTarget($this)`），而 trait 本身不是 class。design 阶段需要决定如何处理 trait 方法中 `$this` 的类型约束——是否需要在 trait 中添加 `@phpstan-require-implements` 注解或 PHP 原生的类型断言？
- A) 不添加额外约束，依赖使用方同时 `implements EventDispatcherInterface`（现有模式，无编译期保证）
- B) 添加 PHP 8.2 的 `@phpstan-require-implements` 注解（静态分析层面保证，无运行时影响）
- C) 其他（请说明）

**A:** B + 运行时断言 — 添加 `@phpstan-require-implements` 注解（静态分析层面），同时在代码中对非 `EventDispatcherInterface` 实例抛出 `LogicException`（运行时保证）

**Q2:** Requirement 8 要求补充测试覆盖，其中 AC 7 要求验证 `getTarget()` 和 `getCurrentTarget()` 返回正确的分发器。在捕获模式（`bubbles = false`）下，分发链从父到子执行，`target` 应指向最初调用 `dispatch()` 的子分发器，而 `currentTarget` 随链变化。是否需要在 AC 中明确区分冒泡和捕获两种模式下 `target` / `currentTarget` 的预期值？
- A) 不需要，AC 7 已足够，具体断言由 design/实现阶段决定
- B) 需要，在 AC 中分别描述冒泡和捕获模式下的预期行为
- C) 其他（请说明）

**A:** B — 已将 AC 7 拆分为冒泡模式（AC 7）和捕获模式（AC 8），分别描述 `getTarget()` 和 `getCurrentTarget()` 的预期行为

**Q3:** Requirement 1 AC 2 指定 PHPUnit 版本为 `^11.0`，但 PHPUnit 12 已发布。`^11.0` 允许 11.x 但不允许 12.x。是否需要调整版本约束以支持更宽的范围（如 `^11.0 || ^12.0`），还是严格锁定 11.x 系列？
- A) 保持 `^11.0`，锁定 11.x 系列（稳定优先）
- B) 改为 `^11.0 || ^12.0`，支持更宽范围
- C) 改为 `>=11.0`，不设上限
- D) 其他（请说明）

**A:** A — 保持 `^11.0`，锁定 11.x 系列
