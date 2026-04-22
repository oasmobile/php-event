<?php
/**
 * Manual test script for Task 10: release-2.0.0 manual testing
 *
 * Covers sub-tasks 10.1 ~ 10.7 in a single automated run.
 * Each sub-task outputs PASS / FAIL with a brief description.
 */

$pass = 0;
$fail = 0;
$results = [];

function report(string $id, bool $ok, string $desc, string $detail = ''): void
{
    global $pass, $fail, $results;
    $status = $ok ? 'PASS' : 'FAIL';
    if ($ok) { $pass++; } else { $fail++; }
    $line = "[$status] $id: $desc";
    if (!$ok && $detail) {
        $line .= "\n        Detail: $detail";
    }
    $results[] = $line;
    echo $line . "\n";
}

// Project root: 4 levels up from __DIR__ (.kiro/specs/release-2.0.0/tests/)
$ROOT = realpath(__DIR__ . '/../../../../');
if (!$ROOT) {
    fwrite(STDERR, "ERROR: Cannot resolve project root from " . __DIR__ . "\n");
    exit(2);
}

// ============================================================
// 10.1 环境准备
// ============================================================
echo "\n=== 10.1 环境准备 ===\n";

// PHP version >= 8.2
$phpVersion = PHP_VERSION;
$phpOk = version_compare($phpVersion, '8.2.0', '>=');
report('10.1a', $phpOk, "PHP >= 8.2 installed", "Got PHP $phpVersion");

// composer install (already done if autoload works, but verify vendor exists)
$autoloadExists = file_exists($ROOT . '/vendor/autoload.php');
report('10.1b', $autoloadExists, "vendor/autoload.php exists (composer install OK)");

// PHPUnit version check
$phpunitBin = $ROOT . '/vendor/bin/phpunit';
$phpunitVersion = '';
if (file_exists($phpunitBin)) {
    $phpunitVersion = trim(shell_exec("php $phpunitBin --version 2>&1") ?? '');
}
$phpunit11 = (bool) preg_match('/PHPUnit 11\.\d+/', $phpunitVersion);
report('10.1c', $phpunit11, "PHPUnit 11.x available", "Got: $phpunitVersion");

// ============================================================
// 10.2 Composer 约束
// ============================================================
echo "\n=== 10.2 Composer 约束 ===\n";

$composerJson = json_decode(file_get_contents($ROOT . '/composer.json'), true);

$phpReq = $composerJson['require']['php'] ?? null;
report('10.2a', $phpReq === '>=8.2', "require.php is '>=8.2'", "Got: " . var_export($phpReq, true));

$phpunitReq = $composerJson['require-dev']['phpunit/phpunit'] ?? null;
report('10.2b', $phpunitReq === '^11.0', "require-dev phpunit is '^11.0'", "Got: " . var_export($phpunitReq, true));

$hasVersion = array_key_exists('version', $composerJson);
report('10.2c', !$hasVersion, "No 'version' field in composer.json");

// ============================================================
// 10.3 类型声明完整性 (Reflection API)
// ============================================================
echo "\n=== 10.3 类型声明完整性 ===\n";

require_once $ROOT . '/vendor/autoload.php';

$targets = [
    \Oasis\Mlib\Event\Event::class,
    \Oasis\Mlib\Event\EventDispatcherInterface::class,
    \Oasis\Mlib\Event\EventDispatcherTrait::class,
];

$typeIssues = [];
foreach ($targets as $className) {
    $ref = new ReflectionClass($className);
    $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);
    foreach ($methods as $method) {
        // Skip inherited methods not declared in this class/interface/trait
        if ($method->getDeclaringClass()->getName() !== $className) {
            continue;
        }
        $methodName = $method->getName();

        // Check return type (constructors cannot have return types in PHP)
        if ($methodName !== '__construct' && !$method->hasReturnType()) {
            $typeIssues[] = "$className::$methodName() missing return type";
        }

        // Check parameter types
        foreach ($method->getParameters() as $param) {
            if (!$param->hasType()) {
                $typeIssues[] = "$className::$methodName() param \${$param->getName()} missing type";
            }
        }
    }
}

$typeOk = empty($typeIssues);
$typeDetail = $typeOk ? '' : implode('; ', $typeIssues);
report('10.3', $typeOk, "All public methods have parameter + return type declarations", $typeDetail);

// ============================================================
// 10.4 拼写修正
// ============================================================
echo "\n=== 10.4 拼写修正 ===\n";

$srcDir = $ROOT . '/src/';
$propogationFound = [];
foreach (glob($srcDir . '*.php') as $file) {
    $content = file_get_contents($file);
    if (preg_match('/Propogation/', $content)) {
        $propogationFound[] = basename($file);
    }
}
$spellingOk = empty($propogationFound);
report('10.4', $spellingOk, "No 'Propogation' misspelling in src/",
    $spellingOk ? '' : "Found in: " . implode(', ', $propogationFound));

// ============================================================
// 10.5 Trait 安全约束
// ============================================================
echo "\n=== 10.5 Trait 安全约束 ===\n";

// Create a temporary class that uses the trait but does NOT implement the interface
// We need to define it in a way that avoids PHPStan issues at analysis time
// but still tests runtime behavior
$traitTestCode = <<<'PHP'
<?php
require_once '%AUTOLOAD%';

// Anonymous-class-like approach: define a named class inline
class _ManualTestTraitOnly {
    use \Oasis\Mlib\Event\EventDispatcherTrait;
    // Intentionally NOT implementing EventDispatcherInterface
    public function getParentEventDispatcher(): ?\Oasis\Mlib\Event\EventDispatcherInterface { return null; }
}

try {
    $obj = new _ManualTestTraitOnly();
    $obj->dispatch('test.event');
    echo 'NO_EXCEPTION';
} catch (\LogicException $e) {
    echo 'LOGIC_EXCEPTION:' . $e->getMessage();
} catch (\Throwable $e) {
    echo 'OTHER_EXCEPTION:' . get_class($e) . ':' . $e->getMessage();
}
PHP;

$autoloadPath = realpath($ROOT . '/vendor/autoload.php');
$traitTestCode = str_replace('%AUTOLOAD%', $autoloadPath, $traitTestCode);
$tmpFile = sys_get_temp_dir() . '/manual_test_trait_safety_' . getmypid() . '.php';
file_put_contents($tmpFile, $traitTestCode);
$traitOutput = trim(shell_exec("php $tmpFile 2>&1") ?? '');
@unlink($tmpFile);

$traitOk = str_starts_with($traitOutput, 'LOGIC_EXCEPTION:');
report('10.5', $traitOk, "Trait without interface throws LogicException on dispatch()",
    $traitOk ? '' : "Got: $traitOutput");

// ============================================================
// 10.6 全量测试
// ============================================================
echo "\n=== 10.6 全量测试 ===\n";

$projectRoot = $ROOT;
$testOutput = shell_exec("cd " . escapeshellarg($projectRoot) . " && php vendor/bin/phpunit 2>&1") ?? '';

// Check for OK result and no deprecation warnings
$testsOk = (bool) preg_match('/OK \(\d+ tests?, \d+ assertions?\)/', $testOutput);
$hasDeprecation = (bool) preg_match('/deprecat/i', $testOutput);
$testFullOk = $testsOk && !$hasDeprecation;
$testDetail = '';
if (!$testsOk) {
    // Extract last few lines for summary
    $lines = explode("\n", trim($testOutput));
    $testDetail = implode("\n        ", array_slice($lines, -5));
}
if ($hasDeprecation) {
    $testDetail .= ($testDetail ? "\n        " : '') . "Deprecation warning detected in output";
}
report('10.6', $testFullOk, "All tests pass, no deprecation warnings", $testDetail);

// ============================================================
// 10.7 SSOT 文档一致性
// ============================================================
echo "\n=== 10.7 SSOT 文档一致性 ===\n";

$docIssues = [];

// --- architecture.md checks ---
$archContent = file_get_contents($ROOT . '/docs/state/architecture.md');

if (!preg_match('/PHP（>=8\.2）/', $archContent) && !preg_match('/PHP.*>=\s*8\.2/', $archContent)) {
    $docIssues[] = "architecture.md: missing PHP >=8.2 in 技术选型";
}
if (!preg_match('/PHPUnit.*\^11\.0/', $archContent)) {
    $docIssues[] = "architecture.md: missing PHPUnit ^11.0 in 技术选型";
}
if (!preg_match('/php\s*>=\s*8\.2.*Composer.*require/i', $archContent)
    && !preg_match('/运行时依赖.*php\s*>=\s*8\.2/u', $archContent)) {
    $docIssues[] = "architecture.md: missing runtime dependency note for php >=8.2";
}

// --- api.md checks ---
$apiContent = file_get_contents($ROOT . '/docs/state/api.md');

// No Propogation in api.md
if (preg_match('/Propogation/', $apiContent)) {
    $docIssues[] = "api.md: still contains 'Propogation' misspelling";
}

// Should have Propagation methods
$expectedMethods = [
    'stopPropagation',
    'stopImmediatePropagation',
    'isPropagationStopped',
    'isPropagationStoppedImmediately',
];
foreach ($expectedMethods as $m) {
    if (strpos($apiContent, $m) === false) {
        $docIssues[] = "api.md: missing method '$m'";
    }
}

// Should have type signatures (return types) in method table
$typeSignatures = [
    'getName(): string',
    'getContext(): mixed',
    'doesBubble(): bool',
    'isCancelled(): bool',
    'cancel(): void',
    'preventDefault(): void',
];
foreach ($typeSignatures as $sig) {
    if (strpos($apiContent, $sig) === false) {
        $docIssues[] = "api.md: missing type signature '$sig'";
    }
}

// Should NOT have the historical Propogation note
if (preg_match('/历史拼写|legacy spelling|Propogation.*历史|Propogation.*legacy/u', $apiContent)) {
    $docIssues[] = "api.md: still contains historical Propogation note";
}

// Interface methods should have type signatures
$ifaceSigs = [
    'getParentEventDispatcher(): ?EventDispatcherInterface',
    'setDelegateDispatcher(?EventDispatcherInterface',
];
foreach ($ifaceSigs as $sig) {
    if (strpos($apiContent, $sig) === false) {
        $docIssues[] = "api.md: missing interface signature '$sig'";
    }
}

$docOk = empty($docIssues);
$docDetail = $docOk ? '' : implode("\n        ", $docIssues);
report('10.7', $docOk, "SSOT docs consistent with implementation", $docDetail);

// ============================================================
// Summary
// ============================================================
echo "\n" . str_repeat('=', 60) . "\n";
echo "SUMMARY: $pass passed, $fail failed out of " . ($pass + $fail) . " checks\n";
echo str_repeat('=', 60) . "\n\n";

foreach ($results as $r) {
    echo "  $r\n";
}

echo "\n";
exit($fail > 0 ? 1 : 0);
