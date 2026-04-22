# Spec Goal: PHP 8 Upgrade

## 来源

- 分支: `release/2.0.0`
- 需求文档: `docs/notes/php8-upgrade.md`

## 背景摘要

`oasis/event` 是一个 PHP 事件分发辅助库，提供 Event 值对象、EventDispatcher 接口与 Trait 实现。当前代码基于 PHP 5.6 时代编写，无类型声明，测试依赖 PHPUnit ^5.1。

note 提出将项目升级到 PHP 8 生态：目标 PHP 版本 8.5，Composer 最低要求 `>=8.2`，测试框架升级到 PHPUnit 11+。作为 2.0.0 大版本发布，允许 breaking change。

现有 API 中存在历史拼写错误 `Propogation`（正确为 `Propagation`），涉及多个公开方法名。借此大版本机会一并修正。

## 目标

- 升级 Composer 依赖：`require` 添加 `"php": ">=8.2"`，`require-dev` 中 PHPUnit 升级到 `^11.0`
- 全面添加 PHP 8 type declaration（参数类型 + 返回类型），包括 `mixed`、union types 等
- 修正 `Propogation` 拼写为 `Propagation`，不保留旧方法名（breaking change）
- 测试迁移到 PHPUnit 11+ 风格，并补充当前未覆盖的测试场景
- 更新 `phpunit.xml` 配置以适配 PHPUnit 11+
- 更新 `docs/state/` 反映升级后的系统状态

## 不做的事情（Non-Goals）

- 不在 `composer.json` 中声明 `version` 字段（继续由 Git tag 决定）
- 不保留旧拼写方法名作为 deprecated alias
- 不改变库的功能行为和架构设计
- 不添加新的运行时依赖

## Clarification 记录

### Q1: Type declaration 策略

- 选项: A) 全面添加 type declaration / B) 仅添加明确无歧义的部分 / C) 不改动签名 / D) 补充说明
- 回答: A — 全面添加，利用 PHP 8 类型系统

### Q2: 历史拼写错误 `Propogation` 处理

- 选项: A) 修正拼写，不保留旧名 / B) 修正拼写，保留旧名为 deprecated alias / C) 不改动 / D) 补充说明
- 回答: A — 修正拼写，不保留旧方法名

### Q3: Composer `require` 与 `version` 字段

- 选项: A) 添加 `php >=8.2`，不声明 version / B) 添加 `php >=8.2`，同时声明 version / C) 补充说明
- 回答: A — 添加 PHP 版本约束，不声明 version 字段

### Q4: 测试覆盖范围

- 选项: A) 仅迁移现有测试 / B) 迁移并补充未覆盖场景 / C) 补充说明
- 回答: B — 迁移到 PHPUnit 11+ 并补充测试覆盖

## 约束与决策

- 2.0.0 大版本，允许 breaking change（方法重命名、类型签名变更）
- PHP 最低版本 `>=8.2`，目标版本 8.5
- PHPUnit `^11.0`
- 不兼容旧版本 PHP，不提供迁移路径
- 拼写修正为一次性 breaking change，无过渡期
