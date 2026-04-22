# 使用说明

## 安装

```bash
composer require oasis/event
```

## 快速开始

### 1. 创建分发器

库不提供具体类，需自行组合接口与 trait：

```php
use Oasis\Mlib\Event\EventDispatcherInterface;
use Oasis\Mlib\Event\EventDispatcherTrait;

class MyDispatcher implements EventDispatcherInterface
{
    use EventDispatcherTrait;
}
```

### 2. 监听与分发

```php
$dispatcher = new MyDispatcher();

// 添加监听器
$dispatcher->addEventListener('user.login', function ($event) {
    echo 'User logged in! Context: ' . $event->getContext();
});

// 分发事件（字符串形式，自动包装为 Event 对象）
$dispatcher->dispatch('user.login', ['userId' => 42]);
```

### 3. 使用 Event 对象

需要控制冒泡或取消行为时，手动创建 Event：

```php
use Oasis\Mlib\Event\Event;

// 不可冒泡、不可取消的事件
$event = new Event('system.shutdown', null, false, false);
$dispatcher->dispatch($event);
```

### 4. 监听器优先级

数值越小越先执行：

```php
$dispatcher->addEventListener('save', $validatorFn, -10);  // 先执行
$dispatcher->addEventListener('save', $persistFn, 0);      // 后执行
```

### 5. 父子分发器（冒泡 / 捕获）

```php
$parent = new MyDispatcher();
$child  = new MyDispatcher();
$child->setParentEventDispatcher($parent);

// 冒泡（默认）：child 的监听器先执行，再到 parent
$child->dispatch('click');

// 捕获：parent 先执行，再到 child
$child->dispatch(new Event('click', null, false));
```

### 6. 停止传播

```php
// 停止向父/子分发器传播，但当前分发器内后续监听器仍执行
$event->stopPropagation();

// 立即停止，当前分发器内后续监听器也不再执行
$event->stopImmediatePropagation();
```

### 7. 取消事件

```php
$event->cancel();       // 或 $event->preventDefault()
$event->isCancelled();  // true
```

若事件构造时 `$cancellable = false`，调用 `cancel()` 会抛出 `LogicException`。

### 8. 委托分发器

将事件转发给另一个分发器处理：

```php
$delegate = new MyDispatcher();
$dispatcher->setDelegateDispatcher($delegate);

// 此后 $dispatcher->dispatch() 会转发给 $delegate
```

## 移除监听器

```php
// 移除特定监听器
$dispatcher->removeEventListener('user.login', $callback);

// 移除某事件的所有监听器
$dispatcher->removeAllEventListeners('user.login');

// 移除所有事件的所有监听器
$dispatcher->removeAllEventListeners();
```
