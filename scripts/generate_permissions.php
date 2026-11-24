<?php
/**
 * Generator data permission dari docs/ACCESS_CONTROL.md.
 * Jalankan: php scripts/generate_permissions.php
 * Output: database/seeders/data/permissions.php
 */

$root = dirname(__DIR__);
$doc = $root . '/docs/ACCESS_CONTROL.md';

if (! file_exists($doc)) {
    fwrite(STDERR, "File tidak ditemukan: {$doc}\n");
    exit(1);
}

$roleColumns = [
    'ADM' => 'administrator',
    'PIM' => 'pimpinan',
    'KAB' => 'kepala_bagian',
    'HRD' => 'hrd',
    'KEU' => 'keuangan',
    'PEG' => 'pegawai',
];

$lines = file($doc, FILE_IGNORE_NEW_LINES);
$permissions = [];
$module = 'umum';
$roleMap = [];

foreach ($lines as $line) {
    if (preg_match('/^###\s+4\.\d+\s+(.+)$/', trim($line), $m)) {
        $module = trim($m[1]);
        continue;
    }

    if (! str_starts_with(trim($line), '|')) {
        continue;
    }

    $cells = array_map('trim', explode('|', trim($line)));
    $cells = array_values(array_filter($cells, fn ($c) => $c !== ''));
    if (count($cells) < 8) {
        continue;
    }

    if (! preg_match('/^`([a-z0-9_.]+)`$/', $cells[0], $codeMatch)) {
        continue;
    }

    $code = $codeMatch[1];
    $description = $cells[1];

    $permissions[$code] = [
        'code' => $code,
        'name' => $description,
        'module' => $module,
    ];

    $columnKeys = array_keys($roleColumns);
    foreach ($columnKeys as $index => $key) {
        $value = $cells[2 + $index] ?? '—';
        if (str_contains($value, '✓')) {
            $roleMap[$roleColumns[$key]][] = $code;
        }
    }
}

ksort($permissions);

$output = "<?php\n\n// Dihasilkan otomatis dari docs/ACCESS_CONTROL.md — jangan ubah manual.\n\nreturn [\n    'permissions' => [\n";
foreach ($permissions as $permission) {
    $output .= sprintf(
        "        ['code' => %s, 'name' => %s, 'module' => %s],\n",
        var_export($permission['code'], true),
        var_export($permission['name'], true),
        var_export($permission['module'], true)
    );
}
$output .= "    ],\n    'roles' => [\n";
foreach ($roleMap as $role => $codes) {
    $output .= sprintf("        %s => [\n", var_export($role, true));
    foreach ($codes as $code) {
        $output .= sprintf("            %s,\n", var_export($code, true));
    }
    $output .= "        ],\n";
}
$output .= "    ],\n];\n";

$targetDir = $root . '/database/seeders/data';
if (! is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

file_put_contents($targetDir . '/permissions.php', $output);

printf("Permission: %d, role: %d\n", count($permissions), count($roleMap));
