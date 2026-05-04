# Changelog v3.0.2

本文件记录 v3.0.2 hotfix 的变更内容。

---

## 修复的 Issue

无。

---

## 工程变更

### 依赖升级：PHP 8.5 + PHPUnit 13

- `composer.json`：PHP 版本约束从 `>=8.2` 升级为 `>=8.5`
- `composer.json`：PHPUnit 版本约束从 `^11.0` 升级为 `^13.0`
- `composer.lock`：依赖树同步更新
- `phpunit.xml`：schema 从 PHPUnit 11.0 更新为 13.0

### 测试代码适配 PHPUnit 13

- `ut/EventTest.php`：移除共享 `mocked_subscriber` 属性，改为各测试方法局部创建 mock，消除 PHPUnit 13 产生的 24 个 notice

---

## 文档同步

- `docs/state/architecture.md`：版本信息更新（PHP 8.5、PHPUnit 13）
- `PROJECT.md`：PHPUnit 版本更新

---

## 测试覆盖

- 62 tests, 3235 assertions
- 全部通过，0 notice
- 源码无变更，公共 API 无变更
