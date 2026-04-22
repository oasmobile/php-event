# API 定义

命名空间：`Oasis\Mlib\Event`

---

## Event

事件值对象，承载事件名称、上下文数据及传播控制状态。

### 构造

```php
public function __construct(string $name, mixed $context = null, bool $bubbles = true, bool $cancellable = true)
```

- `$bubbles = true`：事件从子分发器向父分发器冒泡
- `$bubbles = false`：事件从最顶层父分发器向子分发器捕获

### 方法

| 方法 | 说明 |
|------|------|
| `getName(): string` | 事件名称 |
| `getContext(): mixed` | 事件上下文 |
| `setContext(mixed $context): void` | 设置上下文 |
| `doesBubble(): bool` | 是否冒泡 |
| `isCancelled(): bool` | 是否已取消 |
| `cancel(): void` | 取消事件（不可取消时抛 `LogicException`） |
| `preventDefault(): void` | `cancel()` 的别名 |
| `stopPropagation(): void` | 停止向后续分发器传播 |
| `stopImmediatePropagation(): void` | 停止传播，且当前分发器内后续监听器也不再执行 |
| `isPropagationStopped(): bool` | 传播是否已停止 |
| `isPropagationStoppedImmediately(): bool` | 是否立即停止 |
| `getTarget(): EventDispatcherInterface` | 最初触发事件的分发器 |
| `setTarget(EventDispatcherInterface $target): void` | 由分发器内部调用 |
| `getCurrentTarget(): EventDispatcherInterface` | 当前正在处理事件的分发器 |
| `setCurrentTarget(EventDispatcherInterface $currentTarget): void` | 由分发器内部调用 |

---

## EventDispatcherInterface

分发器必须实现的接口。

| 方法 | 说明 |
|------|------|
| `getParentEventDispatcher(): ?EventDispatcherInterface` | 获取父分发器 |
| `setParentEventDispatcher(EventDispatcherInterface $parent): void` | 设置父分发器 |
| `dispatch(Event\|string $event, mixed $context = null): void` | 分发事件 |
| `addEventListener(string $name, callable $listener, int $priority = 0): void` | 添加监听器（数值越小优先级越高） |
| `removeEventListener(string $name, callable $listener): void` | 移除监听器 |
| `removeAllEventListeners(string $name = ''): void` | 移除指定事件（或全部）的所有监听器 |
| `setDelegateDispatcher(?EventDispatcherInterface $delegate): void` | 设置委托分发器 |

---

## EventDispatcherTrait

`EventDispatcherInterface` 的默认实现，以 trait 形式提供。

使用该 trait 的类必须同时实现 `EventDispatcherInterface`，否则 `dispatch()` 会抛出 `LogicException`。

### 分发流程

1. 若 `$event` 为字符串，自动包装为 `new Event($event, $context)`
2. 若已设置 `$context` 参数，覆盖事件的 context
3. 若存在 delegate dispatcher，转发给 delegate 后返回
4. 设置 `target` 为当前分发器
5. 构建分发链：从当前分发器沿 `getParentEventDispatcher()` 向上收集
   - `bubbles = true`（默认）：子 → 父（冒泡）
   - `bubbles = false`：父 → 子（捕获，链反转）
6. 依次在链中每个分发器上调用 `doDispatchEvent()`
7. 任一环节 `isPropagationStopped()` 为 true 时中断

### 监听器优先级

- `$priority` 为整数，数值越小越先执行（`ksort` 排序）
- 同优先级按添加顺序执行

### 监听器比较（removeEventListener）

- 字符串回调：`==` 比较
- 数组回调（`[$obj, 'method']`）：逐元素 `==` 比较
- 其它：`===` 严格比较
