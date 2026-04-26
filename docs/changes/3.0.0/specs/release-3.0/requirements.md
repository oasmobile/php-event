# Requirements Document

`oasis/event` 3.0 release 的需求定义文档，所属 spec 目录：`.kiro/specs/release-3.0/`。

---

## Introduction

`oasis/event` 库的 3.0 大版本升级。2.0.0 已完成 PHP 8 基础升级（类型声明、PHPUnit 11 迁移、拼写修正），`composer.json` 要求 `php >=8.2`，但现有代码尚未充分利用 PHP 8.1/8.2 引入的语法特性。本次升级对 `src/` 和 `ut/` 中所有文件进行 PHP 8.1/8.2 语法现代化，包括 constructor promotion、`readonly` 属性、first-class callable syntax、移除冗余 PHPDoc 类型注释等。

作为 major release，允许 breaking change（如属性可见性变更、constructor promotion 影响继承等）。

**不涉及的内容（Non-scope）**：

- 不改变库的功能行为和公开 API 语义
- 不改变测试覆盖策略或测试框架版本
- 不在 `composer.json` 中声明 `version` 字段
- 不升级 PHP 最低版本要求（保持 `>=8.2`）
- 不引入新的运行时或开发依赖
- 不引入 oasis 其他库作为依赖（CR2）

---

## Glossary

- **Library**：`oasis/event` Composer 包，即本项目整体
- **Event**：`Oasis\Mlib\Event\Event` 类，事件值对象
- **EventDispatcherInterface**：`Oasis\Mlib\Event\EventDispatcherInterface`，分发器接口
- **EventDispatcherTrait**：`Oasis\Mlib\Event\EventDispatcherTrait`，分发器默认实现（trait）
- **Composer_Config**：项目根目录的 `composer.json` 文件
- **Test_Suite**：`ut/` 目录下的 PHPUnit 测试集合
- **State_Docs**：`docs/state/` 目录下的 SSOT 文档（`architecture.md`、`api.md`）

---

## Requirements

### Requirement 1: Event 类 constructor promotion

**User Story:** 作为库开发者，我希望 Event 类使用 constructor promotion 声明构造函数参数对应的属性，以便减少样板代码并提高可读性。

#### Acceptance Criteria

1. THE Event SHALL use constructor promotion for the `$name` parameter, combining property declaration and constructor assignment into a single promoted parameter.
2. THE Event SHALL use constructor promotion for the `$context` parameter.
3. THE Event SHALL use constructor promotion for the `$bubbles` parameter.
4. THE Event SHALL use constructor promotion for the `$cancellable` parameter.
5. THE Event SHALL preserve the existing constructor signature's parameter order, types, and default values after applying constructor promotion.
6. THE Event SHALL remove the separate property declarations and constructor body assignments for all promoted parameters.

---

### Requirement 2: Event 类 readonly 属性

**User Story:** 作为库使用者，我希望 Event 类中构造后不可变的属性使用 `readonly` 修饰符，以便在类型层面保证不可变性。

#### Acceptance Criteria

1. THE Event SHALL declare the `$name` property as `readonly`.
2. THE Event SHALL declare the `$bubbles` property as `readonly`.
3. THE Event SHALL declare the `$cancellable` property as `readonly`.
4. THE Event SHALL NOT declare the `$context` property as `readonly`, because `setContext()` allows mutation after construction.
5. THE Event SHALL NOT declare `$cancelled`, `$propagationStopped`, `$propagationStoppedImmediately`, `$target`, or `$currentTarget` as `readonly`, because these properties are mutated during the event lifecycle.

---

### Requirement 3: EventDispatcherTrait 调用与比较现代化

**User Story:** 作为库开发者，我希望 EventDispatcherTrait 使用直接函数调用替代 `call_user_func()`，并现代化回调比较逻辑，以便采用更简洁的现代 PHP 风格。

#### Acceptance Criteria

1. THE EventDispatcherTrait SHALL replace `call_user_func($callback, $event)` with direct callable invocation `$callback($event)` in the `doDispatchEvent()` method.
2. THE EventDispatcherTrait SHALL modernize the callback comparison logic in `removeEventListener()`, replacing the current `is_string`/`is_array` based comparison with a cleaner approach.
3. THE EventDispatcherTrait SHALL preserve the existing listener removal behavior after the comparison logic modernization: string callbacks, array callbacks (`[$obj, 'method']`), and closure callbacks SHALL continue to be correctly matched and removed.
4. THE EventDispatcherTrait SHALL preserve the existing listener execution behavior after the `call_user_func` replacement.

---

### Requirement 4: 移除冗余 PHPDoc 类型注释

**User Story:** 作为库开发者，我希望移除原生类型声明已充分表达的冗余 PHPDoc 类型注释，以便减少维护负担并避免注释与代码不一致。

#### Acceptance Criteria

1. WHEN a method's PHPDoc block only contains `@param` and/or `@return` tags whose types are identical to the native PHP type declarations, THE Library SHALL remove that PHPDoc block entirely.
2. WHEN a PHPDoc block contains additional documentation beyond type tags (such as descriptive text, `@throws`, `@see`, or `@phpstan-*` annotations), THE Library SHALL retain the PHPDoc block but remove the redundant `@param` and `@return` type tags.
3. THE Library SHALL retain PHPDoc blocks that provide type information more specific than the native declaration (such as `@var array<string, array<int, array<int, callable>>>` for an `array` typed property).
4. THE Library SHALL apply this rule to all files in `src/` and `ut/`.

---

### Requirement 5: 测试文件语法现代化

**User Story:** 作为库开发者，我希望测试文件也采用 PHP 8.1/8.2 现代语法，以便整个代码库风格一致。

#### Acceptance Criteria

1. WHEN a test file contains constructor promotion opportunities (e.g., test helper classes with constructors), THE Test_Suite SHALL apply constructor promotion.
2. WHEN a test file contains `call_user_func()` calls, THE Test_Suite SHALL replace them with direct callable invocation.
3. THE Test_Suite SHALL remove redundant PHPDoc type annotations following the same rules as Requirement 4.
4. THE Test_Suite SHALL pass without errors or deprecation warnings after all syntax changes.

---

### Requirement 6: 不适用语法特性的排除确认

**User Story:** 作为库开发者，我希望明确记录经评估后不适用的 PHP 8.1/8.2 语法特性，以便避免在 design 和实现阶段产生歧义。

#### Acceptance Criteria

1. THE Library SHALL NOT introduce `enum` types, because the current codebase contains no constant groups suitable for enum conversion.
2. THE Library SHALL NOT introduce intersection types or DNF types in `src/`, because the current public API does not have applicable type combinations beyond what is already declared.
3. THE Library SHALL NOT introduce Fiber-related changes, because the event dispatching mechanism does not involve asynchronous or coroutine-based control flow.
4. THE Composer_Config SHALL NOT add or remove any entries in `require` or `require-dev` sections.
5. THE Composer_Config SHALL NOT contain a `version` field.

---

### Requirement 7: 功能行为保持不变

**User Story:** 作为库使用者，我希望 3.0 语法升级不改变任何功能行为，以便升级后现有使用方式仍然正确。

#### Acceptance Criteria

1. THE Library SHALL preserve the existing public API method signatures' semantic behavior after all syntax changes.
2. THE Test_Suite SHALL pass all existing tests after all syntax changes, confirming no behavioral regression.
3. THE Event SHALL maintain the same constructor parameter order, types, and default values as the 2.0.0 version.
4. THE EventDispatcherInterface SHALL remain unchanged in method signatures.
5. THE EventDispatcherTrait SHALL maintain the same dispatch flow, listener priority ordering, and propagation control behavior.

---

### Requirement 8: 更新 SSOT 文档

**User Story:** 作为开发者，我希望 state 文档反映语法升级后的系统状态，以便 SSOT 在发布后保持准确。

#### Acceptance Criteria

1. WHEN all code changes are complete, THE State_Docs SHALL update `docs/state/architecture.md` to note that the codebase uses PHP 8.1/8.2 modern syntax (constructor promotion, readonly properties).
2. WHEN all code changes are complete, THE State_Docs SHALL update `docs/state/api.md` to reflect any visibility changes on Event properties resulting from constructor promotion (e.g., promoted parameters default to the declared visibility).
3. THE State_Docs SHALL NOT introduce information that contradicts the actual code state.

---

## Socratic Review

**Q: 每条 requirement 是否都在描述外部可观察的行为？是否混入了实现细节？**

Requirement 1-3 描述的是具体的语法变换——对于一个 library 项目，constructor promotion 和 readonly 会改变属性的可见性和继承行为，属于外部可观察的 API 变更。Requirement 4 描述注释清理，虽然不影响运行时行为，但影响开发者体验和文档准确性。Requirement 5 将同样的规则应用到测试文件。Requirement 6 明确排除不适用的特性，避免 design 阶段的歧义。Requirement 7 作为守护性需求，确保功能行为不变。Requirement 8 维护 SSOT。

Requirement 1 AC 6 提到"移除 separate property declarations and constructor body assignments"，这是 constructor promotion 的必然结果而非额外的实现细节，因为保留它们会导致重复声明的编译错误。

**Q: 是否有遗漏的场景？**

已评估的 PHP 8.1/8.2 语法特性包括：constructor promotion、readonly、first-class callable syntax、enum、intersection/DNF types、Fibers、冗余 PHPDoc 移除。其中 enum、intersection types、Fibers 经评估不适用，已在 Requirement 6 中明确排除。

`EventDispatcherInterface` 是纯接口，无属性或构造函数，不涉及 constructor promotion 或 readonly，因此无需单独的 requirement。

`EventDispatcherTrait` 的属性（`$eventParent`、`$eventListeners`、`$delegateDispatcher`）均为可变状态，不适用 readonly。Trait 无构造函数，不适用 constructor promotion。唯一适用的变更是 `call_user_func` 替换（Requirement 3）。

**Q: 各 requirement 之间是否存在矛盾或重叠？**

Requirement 1（constructor promotion）和 Requirement 2（readonly）都涉及 Event 构造函数参数，但关注点不同：前者合并声明，后者添加不可变约束。两者在实现时自然组合（`readonly` promoted parameter）。Requirement 4（PHPDoc 移除）和 Requirement 1 可能同时作用于 Event 构造函数的 PHPDoc，但不矛盾——promotion 后构造函数 PHPDoc 中的 `@param` 类型标签变为冗余，正好由 Requirement 4 清理。

Requirement 5（测试文件）和 Requirement 4（PHPDoc 移除）在测试文件上有交集，Requirement 5 AC 3 明确引用了 Requirement 4 的规则，保持一致性。

**Q: 与 goal 的 scope / non-goals 是否一致？**

goal.md 列出的目标：constructor promotion、readonly、first-class callable syntax、enum（如适用）、intersection/DNF types（如适用）、Fibers（如适用）、移除冗余 PHPDoc。Requirements 1-6 完整覆盖了所有目标，其中不适用的特性在 Requirement 6 中明确排除。

goal.md 的 Non-Goals 和 CR1-CR3 决策：
- CR1（全面应用）→ Requirements 1-5 覆盖 src/ 和 ut/ 全部文件
- CR2（无需引入 oasis 其他库）→ Non-scope 中明确排除
- CR3（允许 breaking change）→ Introduction 中说明，Requirement 2 的 readonly 和 Requirement 1 的 constructor promotion 均可能产生 breaking change

**Q: scope 边界是否清晰？**

本次升级的边界清晰：仅涉及 PHP 8.1/8.2 语法现代化和相应的文档更新。不涉及功能变更、新依赖、测试框架升级。Requirement 7 作为守护性需求，明确划定了"语法变、行为不变"的边界。

---

## Gatekeep Log

**校验时间**: 2025-07-18
**校验结果**: ⚠️ 已修正后通过

### 修正项

- [术语] 移除 Glossary 中 3 个孤立术语（`Constructor_Promotion`、`Readonly_Property`、`First_Class_Callable`），这些术语未在任何 AC 中作为 Subject 使用。它们描述的是 PHP 语法概念，在 Introduction 和 Requirement 正文中已有充分说明，无需作为 Glossary 条目。

### 合规检查

- [x] 无 TBD / TODO / 待定 / 占位符
- [x] 无空 section 或不完整的列表
- [x] 内部引用一致（术语表术语在 AC 中使用，requirement 编号连续）
- [x] 无 markdown 格式错误
- [x] 一级标题存在且附带定位说明
- [x] Introduction 存在，描述了 feature 范围
- [x] Introduction 明确了不涉及的内容（Non-scope）
- [x] Glossary 存在且非空，格式正确
- [x] Requirements section 存在且包含 8 条 requirement
- [x] 各 section 之间使用 `---` 分隔
- [x] Glossary 中的术语在 AC 中被实际使用（无孤立术语）
- [x] AC 中使用的领域概念在 Glossary 中有定义
- [x] User Story 使用中文行文
- [x] AC 使用 `THE <Subject> SHALL ...` / `WHEN ... THEN ...` 语体
- [x] AC 编号连续，无跳号
- [x] AC 聚焦外部可观察行为，未混入不必要的实现细节
- [x] Goal CR 的 3 项决策（CR1 全面应用、CR2 无需引入、CR3 允许 breaking change）均已体现
- [x] Socratic Review 覆盖了行为边界、场景遗漏、矛盾检查、goal 一致性、scope 清晰度
- [x] 文档整体目的达标：读者可清楚知道要做什么、不做什么、做到什么程度算完成

### Clarification Round

**状态**: 待用户回答

**Q1:** Requirement 1（constructor promotion）会将 Event 的构造函数参数提升为属性，promoted parameter 的可见性由声明决定。当前 Event 的 `$name`、`$context`、`$bubbles`、`$cancellable` 等属性为 `protected`。Constructor promotion 后，design 阶段需要决定这些 promoted 属性的可见性：
- A) 保持 `protected`（`public function __construct(protected readonly string $name, ...)`），与 2.0 行为一致，子类可访问
- B) 改为 `private`（`public function __construct(private readonly string $name, ...)`），收紧可见性，子类通过 getter 访问
- C) 改为 `public readonly`（`public function __construct(public readonly string $name, ...)`），允许外部直接读取属性，减少 getter 调用
- D) 其他（请说明）

**A:** A — 保持 `protected`，与 2.0 一致，子类可直接访问属性。

**Q2:** Requirement 3 要求将 `call_user_func($callback, $event)` 替换为 `$callback($event)`。EventDispatcherTrait 中 `doDispatchEvent()` 方法还使用了 `call_user_func` 调用监听器。除此之外，`removeEventListener()` 中的回调比较逻辑使用了 `is_string`/`is_array` 判断和 `==` 比较。design 阶段是否需要同时现代化回调比较逻辑（如使用 `Closure::fromCallable` 统一化），还是严格限定在 `call_user_func` 替换？
- A) 严格限定：仅替换 `call_user_func`，不改动回调比较逻辑（最小变更，符合 Req 7 行为不变）
- B) 同时现代化回调比较逻辑（可能影响行为，需额外测试覆盖）
- C) 其他（请说明）

**A:** B — 同时现代化回调比较逻辑，需在 design 阶段详细设计并补充测试覆盖。

**Q3:** Requirement 4 要求移除冗余 PHPDoc，Requirement 1 的 constructor promotion 会改变构造函数的形态。当 Event 使用 promoted parameters 后，构造函数上方的 PHPDoc block 包含 `@param` 标签和描述性文本（如 "name of the Event"、"whether the Event should bubble"）。这些描述性文本在 promoted parameter 场景下是否仍有保留价值？
- A) 保留描述性文本，仅移除类型标签（`@param string $name` → 保留描述部分）
- B) 全部移除——promoted parameter 的命名和类型已自文档化，描述性文本冗余
- C) 保留整个 PHPDoc block 不变，因为构造函数是主要入口，文档价值高
- D) 其他（请说明）

**A:** B — 全部移除，promoted parameter 的命名和类型已自文档化。
