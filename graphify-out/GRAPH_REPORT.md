# Graph Report — oasis/event

**生成时间**: 2026-04-25
**版本**: 2.0.0
**节点数**: 44
**边数**: 40
**超边数**: 4

---

## God Nodes

God nodes 是边数最多的核心节点，变更影响面最大。

| 节点 | 类型 | 边数 | 说明 |
|------|------|------|------|
| **EventDispatcherTrait** | trait | 15 | 库的核心实现，连接 Event、Interface、所有概念节点、测试和 spec |
| **Event** | class | 12 | 事件值对象，被 Trait 操作、被 Interface 引用、被测试验证、被文档记录 |
| **EventDispatcherInterface** | interface | 8 | 分发器契约，被 Trait 实现、被 Event 引用、被测试替身实现、被文档记录 |

### 解读

这是一个小型库（3 个源文件），三个核心抽象构成紧密的三角关系：
- `Event` ↔ `EventDispatcherInterface`：双向依赖（Event 持有 target/currentTarget 引用，Interface 的 dispatch 接受 Event）
- `EventDispatcherTrait` → 两者：Trait 是 Interface 的默认实现，同时操作 Event 对象

这种结构是事件分发库的典型模式，耦合度在预期范围内。

---

## Communities

图谱自动检测到 5 个社区（内聚模块）。

### Community 0 — 核心库（Source Code + Concepts）

**成员**: Event, EventDispatcherInterface, EventDispatcherTrait, 所有方法节点, 所有概念节点
**Cohesion Score**: 0.92（高）
**说明**: 库的全部源代码和领域概念。内聚度极高，符合小型库的特征。

### Community 1 — 测试（Tests）

**成员**: EventTest, EventPropertyTest, DummyEventDispatcher, PBTEventDispatcher, EventSubscriberStub
**Cohesion Score**: 0.78（中高）
**说明**: 两套测试（example-based + PBT）及其测试替身。通过 `tests` 和 `uses` 边连接到 Community 0。

### Community 2 — 工程配置（Config）

**成员**: ComposerConfig, PHPUnitConfig
**Cohesion Score**: 0.50（中）
**说明**: Composer 和 PHPUnit 配置。与 Community 0 无直接代码依赖，通过 Community 3（文档）间接关联。

### Community 3 — SSOT 文档（State Docs）

**成员**: StateArchitecture, StateAPI
**Cohesion Score**: 0.67（中）
**说明**: 系统事实来源文档。通过 `documents` 边连接到 Community 0 的核心节点。

### Community 4 — 变更历史（Changes）

**成员**: Spec_release-2.0.0, Changelog_2.0.0
**Cohesion Score**: 0.50（中）
**说明**: v2.0.0 的 spec 和 changelog。通过 `modifies` 边连接到 Community 0 和 Community 2。

---

## Cross-Community Bridges

连接不同社区的桥梁节点，是跨模块交互的关键路径。

| 桥梁节点 | 连接的社区 | 说明 |
|----------|-----------|------|
| **EventDispatcherTrait** | C0 ↔ C1 | 测试通过 DummyEventDispatcher/PBTEventDispatcher 间接测试 Trait |
| **Event** | C0 ↔ C1 | 测试直接构造和验证 Event 对象 |
| **StateAPI** | C0 ↔ C3 | API 文档记录核心库的方法签名 |
| **Spec_release-2.0.0** | C0 ↔ C2 ↔ C4 | Spec 同时修改源代码和配置 |

---

## Surprising Connections

跨社区的意外连接——可能被忽视的耦合点。

| 连接 | 说明 | 风险 |
|------|------|------|
| Event ↔ EventDispatcherInterface（双向依赖） | Event 持有 target/currentTarget（类型为 Interface），Interface 的 dispatch 接受 Event。形成循环依赖。 | **低** — 这是事件分发模式的固有特征，两者在同一命名空间内，不构成架构问题。 |
| EDT::doDispatchEvent → Event（多方法调用） | doDispatchEvent 调用 Event 的 getName、setCurrentTarget、isPropagationStoppedImmediately 三个方法 | **低** — 这是分发逻辑的核心路径，调用面合理。 |

---

## Hyperedges

3+ 个节点共享一个概念/流程的超边。

### HE: 事件分发流程
**节点**: EventDispatcherTrait, Event, BubblingMechanism, CapturingMechanism, PropagationControl, ParentChildChain, DelegateDispatcher
**说明**: dispatch() 的完整流程——字符串包装 → delegate 检查 → 构建分发链（冒泡/捕获） → 逐节点 doDispatchEvent → 传播控制。这是库的核心流程，涉及 7 个节点。

### HE: 监听器生命周期
**节点**: EventDispatcherTrait, ListenerPriority, EDT::doDispatchEvent
**说明**: addEventListener 注册 → ksort 排序 → doDispatchEvent 按优先级执行 → remove 移除。

### HE: Event 状态管理
**节点**: Event, PropagationControl, Event::cancel, Event::preventDefault
**说明**: Event 对象的可变状态管理——context 读写、cancel/preventDefault 取消、传播控制。

### HE: 测试覆盖体系
**节点**: EventTest, EventPropertyTest, DummyEventDispatcher, PBTEventDispatcher, EventSubscriberStub
**说明**: 双层测试体系——example-based（EventTest，26 个测试方法）+ property-based（EventPropertyTest，8 个 property，每个 100+ 次迭代）。

---

## Suggested Questions

基于图谱结构，以下问题可能对理解和维护项目有帮助：

1. **Event 和 EventDispatcherInterface 的双向依赖是否可以解耦？** — 当前 Event 持有 Interface 类型的 target/currentTarget，如果未来需要将 Event 独立为纯值对象，需要打破这个依赖。
2. **doDispatchEvent 是否应该提升为接口方法？** — 当前 doDispatchEvent 是 Trait 的 protected 方法，dispatch() 通过 `$dispatcher->doDispatchEvent()` 调用链中的其他分发器，但 IDE 无法解析（因为 $dispatcher 类型为 Interface）。
3. **DelegateDispatcher 机制的使用场景是什么？** — 图谱显示 delegate 是一个独立的分发路径，与父子链互斥。了解实际使用场景有助于判断是否需要更多测试覆盖。
4. **Property-based 测试是否覆盖了 delegate + 父子链的组合场景？** — 当前 PBT 分别测试冒泡/捕获和 removeAll，但未测试 delegate 与父子链的交互。

---

## File → Node Mapping

快速查找每个文件对应的图谱节点。

| 文件 | 节点 |
|------|------|
| `src/Event.php` | Event, Event::* (15 个方法节点) |
| `src/EventDispatcherInterface.php` | EventDispatcherInterface, EDI::* (7 个方法节点) |
| `src/EventDispatcherTrait.php` | EventDispatcherTrait, EDT::doDispatchEvent |
| `ut/EventTest.php` | EventTest, DummyEventDispatcher, EventSubscriberStub |
| `ut/EventPropertyTest.php` | EventPropertyTest, PBTEventDispatcher |
| `composer.json` | ComposerConfig |
| `phpunit.xml` | PHPUnitConfig |
| `docs/state/architecture.md` | StateArchitecture |
| `docs/state/api.md` | StateAPI |
| `docs/changes/2.0.0/specs/release-2.0.0/` | Spec_release-2.0.0 |
| `docs/changes/2.0.0/CHANGELOG.md` | Changelog_2.0.0 |
