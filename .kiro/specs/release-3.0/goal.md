# Spec Goal: Release 3.0

## 来源

- 分支: `release/3.0`
- 需求文档: 无（用户直接描述）

## 背景摘要

`oasis/event` 是一个 PHP 事件分发辅助库。2.0.0 版本已完成 PHP 8 基础升级（type declaration、PHPUnit 11 迁移、拼写修正），当前 `composer.json` 要求 `php >=8.2`。

但现有代码尚未充分利用 PHP 8.1 / 8.2 引入的语法特性。源文件（`src/` 和 `ut/`）仍使用传统属性声明和构造函数赋值风格，未采用 constructor promotion、`readonly` 属性、`enum`、first-class callable syntax 等现代语法。

此外，项目当前无运行时依赖（`require` 仅有 `php >=8.2`）。作为 oasis 生态的一部分，需要评估是否引入 oasis 其他库作为依赖或升级相关集成。

## 目标

- **PHP 8.2 语法现代化（全文件）**：对 `src/` 和 `ut/` 中所有文件进行 PHP 8.1/8.2 语法升级，包括但不限于：
  - Constructor promotion（构造函数参数提升）
  - `readonly` 属性（适用于不可变字段）
  - First-class callable syntax（`$obj->method(...)` 替代 `Closure::fromCallable`）
  - `enum` 替代常量组（如适用）
  - Intersection types / DNF types（如适用）
  - Fibers 相关改进（如适用）
  - 移除冗余的 PHPDoc 类型注释（当原生类型声明已充分表达时）

## 不做的事情（Non-Goals）

- 不改变库的功能行为和公开 API 语义
- 不改变测试覆盖策略或测试框架版本
- 不在 `composer.json` 中声明 `version` 字段
- 不升级 PHP 最低版本要求（保持 `>=8.2`）
- 不引入新的运行时或开发依赖

## Clarification 记录

### CR1: PHP 8.2 语法升级范围

- 选项: A) 全面应用所有适用的 PHP 8.2 语法升级 / B) 仅 constructor promotion + readonly / C) 补充说明
- 回答: A — 全面应用

### CR2: oasis 其他库升级

- 选项: 用户明确具体库 / 无需引入
- 回答: 无需引入 — 去掉此目标

### CR3: 是否为大版本（Breaking Change）

- 选项: A) 允许 breaking change / B) 不允许 / C) 补充说明
- 回答: A — 3.0 为大版本，允许 breaking change

## 约束与决策

- 3.0 大版本，允许 breaking change（属性可见性变更、constructor promotion 影响继承等）
- PHP 最低版本保持 `>=8.2`
- 不引入新依赖
- 语法升级覆盖 `src/` 和 `ut/` 全部文件
