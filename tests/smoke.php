<?php
declare(strict_types=1);

namespace Adminer {
    const ME     = '?pgsql=localhost&username=test&db=example&';
    const DRIVER = 'pgsql';
    const SERVER = 'localhost';
    const DB     = 'example';
    const LANG   = 'pl';

    abstract class Plugin
    {
    }

    function adminer(): object
    {
        return new class {
            public function tableName(array $status): string
            {
                return h((string) ($status['display'] ?? $status['Name'] ?? ''));
            }
        };
    }

    function is_view(array $status): bool
    {
        return ($status['Engine'] ?? null) === 'view';
    }

    function support(string $feature): bool
    {
        return true;
    }

    function h(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    function lang(int $message): string
    {
        return [50 => 'Select data', 51 => 'Show structure', 89 => 'select'][$message];
    }

    function on(string $event, string $handler): string
    {
        return ' data-on' . $event . '="' . h($handler . '()') . '"';
    }

    function bold(bool $active, string $class = ''): string
    {
        return ' class="' . h(trim(($active ? 'active ' : '') . $class)) . '"';
    }
}

namespace {
    require __DIR__ . '/../table-prefix-groups.php';

    $_GET['select'] = 'crm_contact';
    $_GET['view']   = 'report';

    $tables = [
        'crm_contact' => ['Name' => 'crm_contact'],
        'crm_note'    => ['Name' => 'crm_note'],
        'report'      => ['Name' => 'report', 'Engine' => 'view'],
        'hidden'      => ['Name' => 'hidden', 'display' => ''],
        'dependent'   => ['Name' => 'dependent', 'dependent' => true],
        'partition'   => ['Name' => 'partition', 'partition' => true],
        'inherited'   => ['Name' => 'inherited', 'inherited' => true],
        'odd"<name>'  => ['Name' => 'odd"<name>'],
    ];

    $plugin = new Adminer\AdminerTablePrefixGroups();
    ob_start();
    $handled = $plugin->tablesPrint($tables);
    $html    = (string) ob_get_clean();

    $expected = [
        'data-groups-config=',
        'data-table-name="crm_contact"',
        'class="active select hover adminer-prefix-select-link"',
        'adminer-prefix-eye',
        'select=crm_contact',
        'table=crm_contact',
        'class="active view"',
        'data-view="1"',
        'data-table-name="odd&quot;&lt;name&gt;"',
    ];

    if ($handled !== true) {
        throw new RuntimeException('The plugin must handle the sidebar.');
    }

    foreach ($expected as $fragment) {
        if (!str_contains($html, $fragment)) {
            throw new RuntimeException('Missing rendered fragment: ' . $fragment);
        }
    }

    foreach (['hidden', 'dependent', 'partition', 'inherited'] as $name) {
        if (str_contains($html, 'data-table-name="' . $name . '"')) {
            throw new RuntimeException('Hidden object was rendered: ' . $name);
        }
    }

    if (str_contains($html, '<summary>')) {
        throw new RuntimeException('PHP must not automatically create prefix groups.');
    }

    echo "smoke ok\n";
}
