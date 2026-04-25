# Implementation Plan: Release 3.0

## Overview

`oasis/event` 3.0 PHP 8.1/8.2 语法现代化升级的实现计划。采用 test-first 编排：先写测试确认预期行为，再改代码确认不回归。变更按依赖关系排序：Event 类 → EventDispatcherTrait → PHPDoc 清理 → 测试文件现代化 → SSOT 文档更新。

## Tasks

- [x] 1. Event 类 constructor promotion + readonly（Req 1 + Req 2）
  - [x] 1.1 创建 Smoke Tests 文件 `ut/EventStructureTest.php`（test-first）
    - 创建 `ut/EventStructureTest.php`，使用 Reflection API 编写结构验证测试
    - 验证构造函数参数是否为 promoted（`ReflectionParameter::isPromoted()`）
    - 验证 `$name`、`$bubbles`、`$cancellable` 是否为 readonly（`ReflectionProperty::isReadOnly()`）
    - 验证 `$context` 不是 readonly
    - 验证生命周期属性（`$cancelled`、`$propagationStopped`、`$propagationStoppedImmediately`、`$target`、`$currentTarget`）不是 readonly
    - 验证构造函数参数顺序、类型、默认值与 2.0 一致
    - 验证 EventDispatcherInterface 方法签名不变
    - 确认未引入 `enum`、intersection/DNF types、Fiber 相关变更（排除确认）
    - 此时运行测试应 FAIL（当前代码未使用 promotion/readonly）
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 2.1, 2.2, 2.3, 2.4, 2.5, 6.1, 6.2, 6.3, 6.4, 6.5, 7.3, 7.4_
  - [x] 1.2 实现 Event 类 constructor promotion + readonly
    - 将 `$name`、`$context`、`$bubbles`、`$cancellable` 改为 promoted parameters
    - 为 `$name`、`$bubbles`、`$cancellable` 添加 `readonly` 修饰符（`protected readonly`）
    - `$context` 使用 `protected`（无 readonly，因 `setContext()` 需要修改）
    - 移除原有的独立属性声明和构造函数体赋值
    - 构造函数体变为空
    - 非 promoted 属性（`$target`、`$currentTarget`、`$cancelled`、`$propagationStopped`、`$propagationStoppedImmediately`）保持原样
    - 运行全量测试 `vendor/bin/phpunit`，确认 Smoke Tests 通过且无回归
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 2.1, 2.2, 2.3, 2.4, 2.5, 7.1, 7.2, 7.3_
  - [x] 1.3 Checkpoint — 确认 Event 类变更完成
    - 运行 `vendor/bin/phpunit`，确认全部测试通过且无 deprecation warning
    - 通过后 commit（消息包含 Task 1 范围说明）
    - 如有问题请向用户确认

- [-] 2. EventDispatcherTrait 调用与比较现代化（Req 3）
  - [x] 2.1 编写 Example-Based Tests 和 PBT（test-first）
    - 在 `ut/EventTest.php` 中新增 Example-Based Tests，验证回调比较逻辑：
      - 字符串回调的添加与移除（`addEventListener('e', 'func')` → `removeEventListener('e', 'func')` → dispatch 不触发）
      - 数组回调（同一对象引用）的添加与移除
      - Closure 回调的添加与移除
      - 直接调用替换后监听器仍正确接收 Event 参数
    - 在 `ut/EventPropertyTest.php` 中新增 2 个 PBT property：
      - **Property 1: 回调移除正确性（严格比较）** — 随机事件名 + 随机数量的 Closure 监听器，随机选择一个移除，验证被移除的不再触发、其余正常触发
      - **Property 2: 非目标监听器不受移除影响** — 随机事件名 + 多个监听器，移除其中一个，验证未被移除的全部正常触发
    - 每个 PBT 使用 Eris 生成器，Tag 格式：`// Feature: release-3.0, Property {N}: {property_text}`
    - 运行新增测试确认在当前代码上 PASS（当前 `==` 比较在正常使用场景下与 `===` 行为一致）
    - _Requirements: 3.2, 3.3_
  - [x] 2.2 实现 EventDispatcherTrait 代码变更
    - 在 `doDispatchEvent()` 中将 `call_user_func($callback, $event)` 替换为 `$callback($event)`
    - 在 `removeEventListener()` 中将 `$comp` 闭包和 `!$comp($callback, $listener)` 替换为直接的 `$callback !== $listener` 严格比较
    - 移除 `$comp` 闭包定义
    - 运行全量测试 `vendor/bin/phpunit`，确认所有测试通过
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 7.1, 7.2, 7.5_
  - [-] 2.3 Checkpoint — 确认 EventDispatcherTrait 变更完成
    - 运行 `vendor/bin/phpunit`，确认全部测试通过且无 deprecation warning
    - 通过后 commit（消息包含 Task 2 范围说明）
    - 如有问题请向用户确认

- [ ] 3. 冗余 PHPDoc 移除（Req 4）
  - [~] 3.1 集中清理所有文件的 PHPDoc
    - `src/Event.php`：移除文件头 `Created by PhpStorm` 注释块；移除构造函数 `@param` PHPDoc block
    - `src/EventDispatcherInterface.php`：移除文件头 `Created by PhpStorm` 注释块；移除 `dispatch()` 方法上方的 `/** Dispatches a event */` PHPDoc block
    - `src/EventDispatcherTrait.php`：移除文件头 `Created by PhpStorm` 注释块；保留 class-level `@phpstan-require-implements` PHPDoc；保留 `$eventListeners` 的 `@var` PHPDoc
    - `ut/EventTest.php`：移除文件头 `Created by PhpStorm` 注释块
    - `ut/EventPropertyTest.php`：保留文件头描述性 PHPDoc（PBT 说明）
    - `src/Event.php` 中 `preventDefault()` 的 `/** alias of Event::cancel() */` 保留（描述性文本）
    - 运行全量测试 `vendor/bin/phpunit`，确认无回归
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 5.3_
  - [~] 3.2 Checkpoint — 确认 PHPDoc 清理完成
    - 运行 `vendor/bin/phpunit`，确认全部测试通过且无 deprecation warning
    - 通过后 commit（消息包含 Task 3 范围说明）
    - 如有问题请向用户确认

- [ ] 4. 测试文件语法现代化（Req 5）
  - [~] 4.1 检查并应用测试文件的 PHP 8.1/8.2 语法升级
    - 检查 `ut/EventTest.php` 和 `ut/EventPropertyTest.php` 是否有 constructor promotion 机会（测试辅助类的构造函数）
    - 检查是否有 `call_user_func()` 调用需要替换
    - 检查是否有其它 PHP 8.1/8.2 语法升级机会
    - 注意：两个测试文件已使用现代语法（intersection types、first-class callable 等），预计变更极少
    - 运行全量测试 `vendor/bin/phpunit`，确认无错误和 deprecation warning
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 7.2_
  - [~] 4.2 Checkpoint — 确认测试文件现代化完成
    - 运行 `vendor/bin/phpunit`，确认全部测试通过且无 deprecation warning
    - 通过后 commit（消息包含 Task 4 范围说明）
    - 如有问题请向用户确认

- [ ] 5. SSOT 文档更新（Req 8）
  - [~] 5.1 更新 `docs/state/architecture.md`
    - 技术选型 section：新增说明"代码使用 PHP 8.1/8.2 现代语法（constructor promotion、readonly properties）"
    - _Requirements: 8.1_
  - [~] 5.2 更新 `docs/state/api.md`
    - Event section —「构造」代码块：更新构造函数签名，体现 promoted parameters 和 readonly（如 `protected readonly string $name`）
    - Event section —「构造」下方说明：在 `$bubbles` 和 `$cancellable` 的说明旁注明 `readonly`；新增 `$name` 为 `readonly` 的说明
    - EventDispatcherTrait section —「监听器比较（removeEventListener）」：更新比较逻辑说明，从"字符串 `==` / 数组逐元素 `==` / 其它 `===`"改为"统一使用 `!==` 严格比较"
    - _Requirements: 8.2, 8.3_
  - [~] 5.3 Checkpoint — 确认 SSOT 文档更新完成
    - Review 更新后的 state 文档，确认与实际代码状态一致
    - 通过后 commit（消息包含 Task 5 范围说明）
    - 如有问题请向用户确认

- [ ] 6. 手工测试
  - [~] 6.1 Increment alpha tag
    - 按 release 流程递增 alpha tag
  - [~] 6.2 执行手工测试验证
    - 运行 `vendor/bin/phpunit`，确认全量测试通过
    - 检查无 deprecation warning
    - 确认所有 2.0 PBT 测试在 3.0 代码上仍通过
    - 确认新增的 Smoke Tests 和 PBT 全部通过

- [ ] 7. Code Review
  - [~] 7.1 委托给 code-reviewer sub-agent 执行，review 范围为本 spec 的所有变更

## Notes

- 按 spec-execution 规范执行，commit 随 checkpoint 一起执行
- 所有 tasks 均为 mandatory，无 optional 标记
- 采用 test-first 编排：先写测试确认预期行为，再改代码确认不回归
- Smoke Tests 放在独立的 `ut/EventStructureTest.php`（Design CR Q1 决策）
- 新增 PBT property 添加到现有的 `ut/EventPropertyTest.php`（Design CR Q2 决策）
- PHPDoc 清理集中为一个独立 task（Task 3）（Design CR Q3 决策）
- EventDispatcherTrait 的测试先于代码变更编写（Design CR Q4 决策，TDD 风格）
- Checkpoint 作为每个 top-level task 的最后一个 sub-task
- 每个 task 引用具体的 requirements 编号以确保可追溯性
- Property tests 验证 design 中定义的 Correctness Properties
- 验证命令：`vendor/bin/phpunit`

## Socratic Review

**Q: tasks 是否完整覆盖了 design 中的所有实现项？有无遗漏的模块或接口？**

逐项核对 design 中的变更：
- Event 类 constructor promotion + readonly → Task 1（1.1 测试 + 1.2 实现）
- EventDispatcherTrait `call_user_func` 替换 + 回调比较现代化 → Task 2（2.1 测试 + 2.2 实现）
- PHPDoc 移除（全文件）→ Task 3（3.1 集中清理）
- 测试文件语法现代化 → Task 4（4.1 检查并应用）
- SSOT 文档更新 → Task 5（5.1 architecture.md + 5.2 api.md）
- 排除确认（enum、intersection types、Fibers）→ Task 1.1 中包含排除确认检查
- Composer 配置不变 → 无需专门 task，Code Review 时确认

全部覆盖，无遗漏。

**Q: task 之间的依赖顺序是否正确？是否存在隐含的前置依赖未体现在排序中？**

- Task 1（Event 类）和 Task 2（EventDispatcherTrait）无代码依赖，理论上可并行，但当前串行排列（Event 先于 Trait），与 design 中"变更依赖关系与执行顺序"一致
- Task 3（PHPDoc 清理）依赖 Task 1 完成（promotion 后 PHPDoc 形态变化），排在 Task 1 和 2 之后，正确
- Task 4（测试文件现代化）依赖所有 `src/` 变更完成，排在 Task 3 之后，正确
- Task 5（SSOT 文档）依赖所有代码变更完成，排在最后的实现 task，正确
- Task 6（手工测试）和 Task 7（Code Review）排在所有实现 task 之后，正确

无隐含的前置依赖遗漏。

**Q: 每个 task 的粒度是否合适？**

- Task 1 拆为 3 个 sub-task（测试 + 实现 + checkpoint），粒度合适
- Task 2 拆为 3 个 sub-task（测试 + 实现 + checkpoint），粒度合适
- Task 3 将所有文件的 PHPDoc 清理合并为 1 个 sub-task + checkpoint，符合 Design CR Q3 决策
- Task 4 为 1 个 sub-task + checkpoint，因预计变更极少，粒度合适
- Task 5 拆为 2 个文件更新 + checkpoint，粒度合适

无过粗或过细的 task。

**Q: checkpoint 的设置是否覆盖了关键阶段？**

每个 top-level task 的最后一个 sub-task 都是 checkpoint，包含全量测试运行和 commit。覆盖了所有关键阶段。

**Q: 手工测试是否覆盖了 requirements 中的关键用户场景？**

本次升级为纯语法现代化，不改变功能行为。手工测试（Task 6）通过运行全量自动化测试套件来验证，包括 Smoke Tests（结构验证）、Example-Based Tests（行为验证）、PBT（属性验证）。对于语法升级类 release，自动化测试已充分覆盖关键场景。

**Q: Design CR 的 4 项决策是否都在 tasks 编排中得到体现？**

- CR Q1（Smoke Tests 放在独立的 `ut/EventStructureTest.php`）→ Task 1.1 创建该文件 ✓
- CR Q2（新增 PBT 添加到现有 `ut/EventPropertyTest.php`）→ Task 2.1 在该文件中新增 ✓
- CR Q3（PHPDoc 清理集中为独立 task）→ Task 3 为独立 top-level task ✓
- CR Q4（先写测试再改代码，TDD 风格）→ Task 1.1 先于 1.2，Task 2.1 先于 2.2 ✓

全部体现。


---

## Gatekeep Log

**校验时间**: 2025-07-18
**校验结果**: ⚠️ 已修正后通过

### 修正项

- [结构] 所有 Checkpoint sub-task 补充了 commit 动作（原文仅有验证步骤，缺少 commit）
- [内容] Code Review task（Task 7）简化为"委托给 code-reviewer sub-agent 执行"，移除了展开的 review 文件清单（review checklist 由 code-reviewer agent 自身定义）
- [内容] Notes section 补充了 spec-execution 规范引用、commit 时机说明、Design CR 决策对应关系和验证命令
- [内容] 补充了 Socratic Review section，覆盖 design 全覆盖、依赖顺序、task 粒度、checkpoint 设置、手工测试覆盖、Design CR 决策体现
- [语体] Overview 和 sub-task 标签中的"TDD 风格（RED/GREEN）"修正为"test-first 编排"——Task 2.1 的测试在当前代码上应 PASS（行为兼容），不符合严格的 RED-GREEN TDD 语义
- [内容] Task 1.1 补充了 Req 6.1-6.3（排除确认：enum、intersection/DNF types、Fibers）的引用，原文仅引用了 6.4-6.5

### 合规检查

- [x] 无 TBD / TODO / 待定 / 占位符
- [x] 无空 section 或不完整的列表
- [x] 内部引用一致（requirement 编号、design 中的模块名）
- [x] checkbox 语法正确（`- [ ]`）
- [x] 无 markdown 格式错误
- [x] `## Tasks` section 存在
- [x] 手工测试类 top-level task 的第一个 sub-task 是 "Increment alpha tag"（Release spec）
- [x] 最后一个 top-level task 是 Code Review
- [x] 倒数第二个 top-level task 是手工测试
- [x] 自动化实现 task 排在手工测试和 Code Review 之前
- [x] 所有 task 使用 `- [ ]` checkbox 语法
- [x] top-level task 有序号（1-7），sub-task 有层级序号
- [x] 序号连续，无跳号
- [x] 每个实现类 sub-task 引用了具体的 requirements 条款
- [x] requirements.md 中的每条 requirement 至少被一个 task 引用（Req 1-8 全覆盖）
- [x] 引用的 requirement 编号和 AC 编号在 requirements.md 中确实存在
- [x] top-level task 按依赖关系排序（Event → Trait → PHPDoc → 测试现代化 → SSOT → 手工测试 → Code Review）
- [x] 无循环依赖
- [x] 已对照 graphify 图谱验证跨模块依赖（C0↔C1 桥梁节点、C0↔C3 桥梁节点），task 排序与模块依赖一致
- [x] Checkpoint 作为每个 top-level task 的最后一个 sub-task
- [x] Checkpoint 包含具体验证命令和 commit 动作
- [x] test-first 编排：Task 1.1 先于 1.2，Task 2.1 先于 2.2
- [x] 每个 sub-task 足够具体，可独立执行
- [x] 无过粗或过细的 task
- [x] 所有 task 均为 mandatory
- [x] 手工测试 top-level task 存在（Task 6）
- [x] Code Review 是最后一个 top-level task（Task 7），描述为委托给 code-reviewer sub-agent
- [x] `## Notes` section 存在，包含 spec-execution 引用和 commit 时机说明
- [x] `## Socratic Review` section 存在，覆盖充分
- [x] Design CR 4 项决策（Q1 Smoke Tests 文件、Q2 PBT 文件、Q3 PHPDoc 独立 task、Q4 TDD 风格）均在 tasks 编排中体现
- [x] Design 全覆盖（Req 1-8 对应的所有实现项均有 task）
- [x] 每个 sub-task 可独立执行
- [x] 验收闭环完整（checkpoint + 手工测试 + code review）
- [x] 执行路径无歧义
- [x] 文档整体目的达标：可直接执行的实现计划
