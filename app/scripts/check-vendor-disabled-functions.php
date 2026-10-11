<?php

declare(strict_types=1);

/**
 * Fails when a production Composer package calls a function that production disables (`disable_functions` in the
 * production php.ini), unless the call is listed in vendor-disabled-function-calls.php.
 *
 * Such a call works in dev and tests, then throws "Call to undefined function" in production, so a new one must be
 * reviewed before it ships. Dev-only packages are skipped: they never run in production.
 *
 * Static only: dynamic calls such as call_user_func('exec') are not detected.
 *
 * Usage: php scripts/check-vendor-disabled-functions.php [path/to/production/php.ini]
 * The php.ini defaults to the one PHP loaded, which in the php container is the production file.
 * Runs from Composer's post-install-cmd and post-update-cmd, and in the production image build.
 */

const VENDOR_DIR = __DIR__ . '/../vendor';

$iniPath = $argv[1] ?? php_ini_loaded_file();

if (!is_string($iniPath) || !is_file($iniPath)) {
    fwrite(STDERR, "Production php.ini not found\n");
    exit(2);
}

$disabled = disabledFunctions($iniPath);

/** @var array<string, array<string, non-empty-string>> */
$allowed = require __DIR__ . '/vendor-disabled-function-calls.php';

$found = [];

foreach (productionPackageDirectories() as $package => $directory) {
    foreach (calledFunctions($directory, $disabled) as $function => $locations) {
        $found[$package][$function] = $locations;
    }
}

$failures = [];

foreach ($found as $package => $functions) {
    foreach ($functions as $function => $locations) {
        if (!isset($allowed[$package][$function])) {
            $failures[] = "{$package} calls {$function}(), which production disables:\n    " . implode("\n    ", $locations);
        }
    }
}

foreach ($allowed as $package => $functions) {
    foreach (array_keys($functions) as $function) {
        if (!isset($found[$package][$function])) {
            $failures[] = "{$package} no longer calls {$function}() (or isn't installed): remove it from vendor-disabled-function-calls.php";
        }
    }
}

if ($failures !== []) {
    fwrite(STDERR, implode("\n", $failures) . "\n\n"
        . "Each call either needs an entry in scripts/vendor-disabled-function-calls.php saying why it can't run here,\n"
        . "or the function must be re-enabled in deploy/php/php.ini.\n");
    exit(1);
}

echo 'Production vendor code calls no disabled functions beyond the ' . array_sum(array_map(count(...), $allowed)) . " reviewed in vendor-disabled-function-calls.php\n";

/**
 * @return array<string, true>
 */
function disabledFunctions(string $iniPath): array
{
    $ini = parse_ini_string((string) file_get_contents($iniPath), false, INI_SCANNER_RAW);
    $list = is_array($ini) ? ($ini['disable_functions'] ?? '') : '';

    if (!is_string($list) || trim($list) === '') {
        fwrite(STDERR, "{$iniPath} has no disable_functions\n");
        exit(2);
    }

    return array_fill_keys(preg_split('/[\s,]+/', strtolower(trim($list)), -1, PREG_SPLIT_NO_EMPTY) ?: [], true);
}

/**
 * @return array<string, string> Package name => install directory
 */
function productionPackageDirectories(): array
{
    $installed = json_decode((string) file_get_contents(VENDOR_DIR . '/composer/installed.json'), true);

    if (!is_array($installed) || !is_array($installed['packages'] ?? null)) {
        fwrite(STDERR, "vendor/composer/installed.json is missing or unreadable\n");
        exit(2);
    }

    $devPackageNames = is_array($installed['dev-package-names'] ?? null) ? $installed['dev-package-names'] : [];
    $devPackages = array_fill_keys(array_filter($devPackageNames, is_string(...)), true);
    $directories = [];

    foreach ($installed['packages'] as $package) {
        if (!is_array($package) || !is_string($package['name'] ?? null) || !is_string($package['install-path'] ?? null)) {
            continue;
        }

        if (!isset($devPackages[$package['name']])) {
            $directory = realpath(VENDOR_DIR . '/composer/' . $package['install-path']);

            if ($directory !== false) {
                $directories[$package['name']] = $directory;
            }
        }
    }

    ksort($directories);

    return $directories;
}

/**
 * @param array<string, true> $functions
 * @return array<string, list<string>> Function => "file:line" of each call
 */
function calledFunctions(string $directory, array $functions): array
{
    $calls = [];
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));

    foreach ($files as $file) {
        if (!$file instanceof SplFileInfo || $file->getExtension() !== 'php') {
            continue;
        }

        $tokens = token_get_all((string) file_get_contents($file->getPathname()));

        foreach ($tokens as $i => $token) {
            if (!is_array($token) || ($token[0] !== T_STRING && $token[0] !== T_NAME_FULLY_QUALIFIED)) {
                continue;
            }

            $name = strtolower(ltrim($token[1], '\\'));

            if (!isset($functions[$name]) || !isGlobalFunctionCall($tokens, $i)) {
                continue;
            }

            $calls[$name][] = substr($file->getPathname(), strlen($directory) + 1) . ':' . $token[2];
        }
    }

    return $calls;
}

/**
 * True for `name(` and `\name(`, false for methods, declarations, `new name(` and constants.
 *
 * @param array<int, array{0: int, 1: string, 2: int}|string> $tokens
 */
function isGlobalFunctionCall(array $tokens, int $index): bool
{
    $next = $index + 1;
    while (is_array($tokens[$next] ?? null) && in_array($tokens[$next][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
        $next++;
    }

    if (($tokens[$next] ?? null) !== '(') {
        return false;
    }

    $previous = $index - 1;
    while (is_array($tokens[$previous] ?? null) && in_array($tokens[$previous][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
        $previous--;
    }

    $before = $tokens[$previous] ?? null;

    return !is_array($before)
        || !in_array($before[0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_NEW, T_CONST], true);
}
