# 架构与工程约束

## 技术选型

- **语言**：PHP（>=8.5）
- **包类型**：Composer library（`oasis/event`）
- **自动加载**：PSR-4，`Oasis\Mlib\Event\` → `src/`
- **测试**：PHPUnit ^13.0，配置文件 `phpunit.xml`，测试目录 `ut/`
- **运行时依赖**：`php >=8.5`（Composer `require`）
- **语法风格**：代码使用 PHP 8.5 现代语法（constructor promotion、readonly properties、直接 callable 调用、PHPUnit attribute）

## 分层结构

```
src/
├── Event.php                      # 事件值对象
├── EventDispatcherInterface.php   # 分发器接口
└── EventDispatcherTrait.php       # 分发器默认实现（trait）
```

库本身不提供具体的 EventDispatcher 类；使用方通过 `use EventDispatcherTrait` + `implements EventDispatcherInterface` 组合到自己的类中。

## 测试策略

- 单元测试位于 `ut/`，使用 PHPUnit 13 mock API 验证回调调用
- 测试中定义了 `DummyEventDispatcher`（实现接口 + 使用 trait）作为测试替身
- Property-based 测试使用 Eris `TestTrait` + 自定义随机生成器，每个 property 至少 100 次迭代
- 运行命令：`vendor/bin/phpunit`
