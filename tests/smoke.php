<?php
declare(strict_types=1);

namespace Adminer {
    const ME = '?';

    abstract class Plugin
    {
    }

    class SmokeAdminer
    {
        /**
         * @param array<string,mixed> $status
         */
        public function tableName(array $status): string
        {
            return (string) ($status['display'] ?? $status['Name'] ?? '');
        }
    }

    function adminer(): SmokeAdminer
    {
        static $adminer;

        if (!$adminer) {
            $adminer = new SmokeAdminer();
        }

        return $adminer;
    }

    /**
     * @param array<string,mixed> $status
     */
    function is_view(array $status): bool
    {
        return ($status['Engine'] ?? null) === 'view';
    }

    function h(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    function lang(string $message): string
    {
        return $message;
    }

    function bold(bool $active, string $class = ''): string
    {
        $classes = trim(($active ? 'active ' : '') . $class);

        return $classes === '' ? '' : ' class="' . h($classes) . '"';
    }
}

namespace {
    require __DIR__ . '/../table-prefix-groups.php';

    $plugin = new Adminer\AdminerTablePrefixGroups();
    $method = new ReflectionMethod($plugin, 'prefixGroupsBuildGroups');
    $method->setAccessible(true);

    $tables = [
        'crm' => ['Name' => 'crm'],
        'crm_contact' => ['Name' => 'crm_contact'],
        'crm_note' => ['Name' => 'crm_note'],
        'plm_order' => ['Name' => 'plm_order'],
        'plm_item' => ['Name' => 'plm_item'],
        'users' => ['Name' => 'users'],
        'hidden_a' => ['Name' => 'hidden_a', 'display' => ''],
        'hidden_b' => ['Name' => 'hidden_b', 'display' => ''],
        'part_a' => ['Name' => 'part_a', 'partition' => true],
        'part_b' => ['Name' => 'part_b', 'partition' => true],
        'child_a' => ['Name' => 'child_a', 'inherited' => true],
        'child_b' => ['Name' => 'child_b', 'inherited' => true],
        'report_a' => ['Name' => 'report_a', 'Engine' => 'view'],
        'report_b' => ['Name' => 'report_b', 'Engine' => 'view'],
    ];

    $result = $method->invoke($plugin, $tables);

    assertSame(['crm', 'crm_contact', 'crm_note'], $result['groups']['crm']['tables'] ?? null, 'crm base table stays in crm group');
    assertSame(['plm_item', 'plm_order'], $result['groups']['plm']['tables'] ?? null, 'plm prefixed tables are grouped');
    assertSame(['report_a', 'report_b'], $result['groups']['report']['views'] ?? null, 'views can create a prefix group');
    assertSame(['users'], $result['ungrouped']['tables'] ?? null, 'only visible ungrouped table remains in Other');
    assertMissingGroup($result['groups'], 'hidden');
    assertMissingGroup($result['groups'], 'part');
    assertMissingGroup($result['groups'], 'child');
    assertSelectLinkUsesAdminerActiveClass($plugin);
    assertTextSelectLinkCanDisableIcon();
    assertNoMethod($plugin, 'selectLink');
    assertNoMethod($plugin, 'structureLink');
    assertPrivateHelpersArePrefixed($plugin);

    echo "smoke ok\n";

    /**
     * @param mixed $actual
     * @param mixed $expected
     */
    function assertSame($expected, $actual, string $message): void
    {
        if ($actual !== $expected) {
            fwrite(STDERR, $message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
            exit(1);
        }
    }

    /**
     * @param array<string,mixed> $groups
     */
    function assertMissingGroup(array $groups, string $name): void
    {
        if (array_key_exists($name, $groups)) {
            fwrite(STDERR, "Unexpected group: $name\n");
            exit(1);
        }
    }

    function assertNoMethod(object $object, string $name): void
    {
        if (method_exists($object, $name)) {
            fwrite(STDERR, "Unexpected Adminer hook collision: $name\n");
            exit(1);
        }
    }

    function assertSelectLinkUsesAdminerActiveClass(Adminer\AdminerTablePrefixGroups $plugin): void
    {
        $_GET['select'] = 'crm_contact';

        $method = new ReflectionMethod($plugin, 'prefixGroupsRenderSelectLink');
        $method->setAccessible(true);
        $html = $method->invoke($plugin, 'crm_contact');

        unset($_GET['select']);

        if (!is_string($html) || !str_contains($html, 'class="active select adminer-prefix-select-link"')) {
            fwrite(STDERR, "Active select link must keep Adminer's active select classes\n$html\n");
            exit(1);
        }

        if (!str_contains($html, 'adminer-prefix-eye')) {
            fwrite(STDERR, "Default select link must render the eye icon\n$html\n");
            exit(1);
        }
    }

    function assertTextSelectLinkCanDisableIcon(): void
    {
        $_GET['select'] = 'crm_contact';

        $plugin = new Adminer\AdminerTablePrefixGroups(
            'adminer-prefix-groups.css',
            null,
            null,
            null,
            '_',
            false
        );
        $method = new ReflectionMethod($plugin, 'prefixGroupsRenderSelectLink');
        $method->setAccessible(true);
        $html = $method->invoke($plugin, 'crm_contact');

        unset($_GET['select']);

        if (!is_string($html) || !str_contains($html, 'class="active select"')) {
            fwrite(STDERR, "Text select link must keep Adminer's active select classes\n$html\n");
            exit(1);
        }

        if (str_contains($html, 'adminer-prefix-eye')) {
            fwrite(STDERR, "Text select link must not render the eye icon\n$html\n");
            exit(1);
        }
    }

    function assertPrivateHelpersArePrefixed(object $object): void
    {
        $class = new ReflectionClass($object);

        foreach ($class->getMethods(ReflectionMethod::IS_PRIVATE) as $method) {
            if (!str_starts_with($method->getName(), 'prefixGroups')) {
                fwrite(STDERR, 'Private helper must use prefixGroups prefix: ' . $method->getName() . "\n");
                exit(1);
            }
        }
    }
}
