<?php
declare(strict_types=1);

namespace Adminer;

/**
 * Group Adminer sidebar tables by the part before the configured separator.
 *
 * This plugin intentionally changes only the left navigation table list. It
 * keeps Adminer's native table links and active-item classes.
 */
class AdminerTablePrefixGroups extends Plugin
{
    private string $cssFile;
    private ?string $otherLabel;
    private ?string $tableCountLabel;
    private ?string $viewCountLabel;
    private string $separator;
    private bool $useSelectIcon;

    /**
     * @var array<string,array{other: string, tables: string, views: string}>
     */
    private array $labelsByLanguage = [
        'ar' => ['other' => 'أخرى', 'tables' => 'جداول', 'views' => 'عروض'],
        'bn' => ['other' => 'অন্যান্য', 'tables' => 'টেবিল', 'views' => 'ভিউ'],
        'cs' => ['other' => 'Ostatní', 'tables' => 'tabulky', 'views' => 'pohledy'],
        'de' => ['other' => 'Andere', 'tables' => 'Tabellen', 'views' => 'Ansichten'],
        'en' => ['other' => 'Other', 'tables' => 'tables', 'views' => 'views'],
        'es' => ['other' => 'Otros', 'tables' => 'tablas', 'views' => 'vistas'],
        'fi' => ['other' => 'Muut', 'tables' => 'taulut', 'views' => 'näkymät'],
        'fr' => ['other' => 'Autres', 'tables' => 'tables', 'views' => 'vues'],
        'hi' => ['other' => 'अन्य', 'tables' => 'तालिकाएँ', 'views' => 'दृश्य'],
        'id' => ['other' => 'Lainnya', 'tables' => 'tabel', 'views' => 'tampilan'],
        'it' => ['other' => 'Altro', 'tables' => 'tabelle', 'views' => 'viste'],
        'nl' => ['other' => 'Overig', 'tables' => 'tabellen', 'views' => 'views'],
        'pl' => ['other' => 'Pozostałe', 'tables' => 'tab.', 'views' => 'widoki'],
        'pt' => ['other' => 'Outros', 'tables' => 'tabelas', 'views' => 'visualizações'],
        'pt-br' => ['other' => 'Outros', 'tables' => 'tabelas', 'views' => 'visualizações'],
        'ru' => ['other' => 'Другие', 'tables' => 'таблицы', 'views' => 'представления'],
        'sk' => ['other' => 'Ostatné', 'tables' => 'tabuľky', 'views' => 'pohľady'],
        'sv' => ['other' => 'Övriga', 'tables' => 'tabeller', 'views' => 'vyer'],
        'ur' => ['other' => 'دیگر', 'tables' => 'جدولیں', 'views' => 'مناظر'],
        'uk' => ['other' => 'Інші', 'tables' => 'таблиці', 'views' => 'подання'],
        'zh' => ['other' => '其他', 'tables' => '表', 'views' => '视图'],
        'zh-tw' => ['other' => '其他', 'tables' => '資料表', 'views' => '檢視'],
    ];

    public function __construct(
        string $cssFile = 'adminer-prefix-groups.css',
        ?string $otherLabel = null,
        ?string $tableCountLabel = null,
        ?string $viewCountLabel = null,
        string $separator = '_',
        bool $useSelectIcon = true
    ) {
        $this->cssFile = $cssFile;
        $this->otherLabel = $otherLabel;
        $this->tableCountLabel = $tableCountLabel;
        $this->viewCountLabel = $viewCountLabel;
        $this->separator = $separator;
        $this->useSelectIcon = $useSelectIcon;
    }

    /**
     * Add the optional CSS file when it is mounted next to Adminer.
     */
    public function head(?bool $dark = null): void
    {
        if (!is_readable($this->cssFile)) {
            return;
        }

        $version = crc32((string) file_get_contents($this->cssFile));
        echo '<link rel="stylesheet" type="text/css" href="' . h($this->cssFile . '?v=' . $version) . '">' . "\n";
    }

    /**
     * Replace only Adminer's sidebar table list.
     *
     * Adminer passes table-status rows keyed by table name. Returning true
     * tells Adminer that the default renderer has been handled.
     */
    public function tablesPrint($tables): ?bool
    {
        if (!is_array($tables) || $tables === []) {
            return null;
        }

        $groupedTables = $this->prefixGroupsBuildGroups($tables);
        $labels = $this->prefixGroupsLabels();

        echo "<ul id='tables' class='adminer-prefix-groups'>";
        echo script("mixin(qs('#tables'), {onmouseover: menuOver, onmouseout: menuOut});");

        foreach ($groupedTables['groups'] as $prefix => $group) {
            $this->prefixGroupsPrintGroup($prefix, $group['tables'], $group['views'], $tables, $labels);
        }

        if ($groupedTables['ungrouped']['tables'] !== [] || $groupedTables['ungrouped']['views'] !== []) {
            $this->prefixGroupsPrintGroup(
                $labels['other'],
                $groupedTables['ungrouped']['tables'],
                $groupedTables['ungrouped']['views'],
                $tables,
                $labels,
                true
            );
        }

        echo "</ul>\n";

        return true;
    }

    /**
     * Build groups using the configured prefix rule:
     * - prefix is the text before the configured separator,
     * - a group is created when at least two prefixed objects exist,
     * - or when one prefixed object exists and a base object with the prefix name exists.
     *
     * @param array<string,array<string,mixed>> $tables
     * @return array{groups: array<string,array{tables: string[], views: string[]}>, ungrouped: array{tables: string[], views: string[]}}
     */
    private function prefixGroupsBuildGroups(array $tables): array
    {
        $tableNames = [];
        $viewNames = [];
        $tablesByPrefix = [];
        $viewsByPrefix = [];
        $visibleTables = [];

        foreach ($tables as $name => $status) {
            if ($this->prefixGroupsIsHiddenTableStatus($status)) {
                continue;
            }

            if (adminer()->tableName($status) === '') {
                continue;
            }

            $visibleTables[$name] = $status;

            if (is_view($status)) {
                $viewNames[$name] = true;
            } else {
                $tableNames[$name] = true;
            }

            $prefix = $this->prefixGroupsPrefixOf((string) $name);
            if ($prefix === null) {
                continue;
            }

            if (is_view($status)) {
                $viewsByPrefix[$prefix][] = (string) $name;
            } else {
                $tablesByPrefix[$prefix][] = (string) $name;
            }
        }

        $groups = [];
        $usedNames = [];
        $prefixes = $this->prefixGroupsNaturalUnique(array_merge(array_keys($tablesByPrefix), array_keys($viewsByPrefix)));

        foreach ($prefixes as $prefix) {
            $prefixedTables = $tablesByPrefix[$prefix] ?? [];
            $prefixedViews = $viewsByPrefix[$prefix] ?? [];
            $hasBaseObject = isset($tableNames[$prefix]) || isset($viewNames[$prefix]);

            if (!$this->prefixGroupsShouldCreateGroup($prefixedTables, $prefixedViews, $hasBaseObject)) {
                continue;
            }

            $groupTables = $prefixedTables;
            if (isset($tableNames[$prefix])) {
                $groupTables[] = $prefix;
            }

            $groupViews = $prefixedViews;
            if (isset($viewNames[$prefix])) {
                $groupViews[] = $prefix;
            }

            $groupTables = $this->prefixGroupsNaturalUnique($groupTables);
            $groupViews = $this->prefixGroupsNaturalUnique($groupViews);
            $groups[$prefix] = [
                'tables' => $groupTables,
                'views' => $groupViews,
            ];

            foreach (array_merge($groupTables, $groupViews) as $name) {
                $usedNames[$name] = true;
            }
        }

        return [
            'groups' => $groups,
            'ungrouped' => $this->prefixGroupsUngroupedNames($visibleTables, $usedNames),
        ];
    }

    /**
     * @param string[] $prefixedTables
     * @param string[] $prefixedViews
     */
    private function prefixGroupsShouldCreateGroup(array $prefixedTables, array $prefixedViews, bool $hasBaseObject): bool
    {
        $prefixedObjects = count($prefixedTables) + count($prefixedViews);

        return $prefixedObjects >= 2 || ($hasBaseObject && $prefixedObjects >= 1);
    }

    /**
     * @param string[] $tableNames
     * @param string[] $viewNames
     * @param array<string,array<string,mixed>> $allTables
     * @param array{other: string, tables: string, views: string} $labels
     */
    private function prefixGroupsPrintGroup(
        string $label,
        array $tableNames,
        array $viewNames,
        array $allTables,
        array $labels,
        bool $open = false
    ): void {
        $names = array_merge($tableNames, $viewNames);
        if ($names === []) {
            return;
        }

        echo '<li class="adminer-prefix-group">';
        echo '<details' . ($open || $this->prefixGroupsContainsCurrentTable($names) ? ' open' : '') . '>';
        echo '<summary>';
        echo '<span class="adminer-prefix-label">' . h($label) . '</span>';
        echo '<span class="adminer-prefix-count">' . h($this->prefixGroupsFormatCount($tableNames, $viewNames, $labels)) . '</span>';
        echo '</summary>';
        echo '<ul>';

        foreach ($tableNames as $name) {
            $this->prefixGroupsPrintTableLink($name, $allTables[$name]);
        }

        foreach ($viewNames as $name) {
            $this->prefixGroupsPrintTableLink($name, $allTables[$name]);
        }

        echo '</ul>';
        echo '</details>';
        echo '</li>';
    }

    /**
     * @param array<string,mixed> $status
     */
    private function prefixGroupsPrintTableLink(string $name, array $status): void
    {
        $displayName = adminer()->tableName($status);
        if ($displayName === '') {
            return;
        }

        echo '<li data-table-name="' . h($name) . '">';
        echo $this->prefixGroupsRenderSelectLink($name);
        echo ' ';
        echo $this->prefixGroupsRenderStructureLink($name, $status, $displayName);
        echo "</li>\n";
    }

    private function prefixGroupsRenderSelectLink(string $name): string
    {
        $isActive = ($_GET['select'] ?? null) === $name || ($_GET['edit'] ?? null) === $name;

        if (!$this->useSelectIcon) {
            return '<a href="' . h(ME) . 'select=' . urlencode($name) . '"'
                . bold($isActive, 'select')
                . " title='" . h(lang('Select data')) . "'>"
                . h(lang('select'))
                . '</a>';
        }

        return '<a href="' . h(ME) . 'select=' . urlencode($name) . '"'
            . bold($isActive, 'select adminer-prefix-select-link')
            . " title='" . h(lang('Select data')) . "'"
            . " aria-label='" . h(lang('Select data')) . "'>"
            . '<svg class="adminer-prefix-eye" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false">'
            . '<path fill="currentColor" d="M12 5c-5 0-9 5-10 7 1 2 5 7 10 7s9-5 10-7c-1-2-5-7-10-7Zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Zm0-2a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/>'
            . '</svg>'
            . '<span class="adminer-prefix-select-text">' . h(lang('select')) . '</span>'
            . '</a>';
    }

    /**
     * @param array<string,mixed> $status
     */
    private function prefixGroupsRenderStructureLink(string $name, array $status, string $displayName): string
    {
        if (!support('table') && !support('indexes')) {
            return '<span>' . $displayName . '</span>';
        }

        $isActive = in_array($name, [
            $_GET['table'] ?? null,
            $_GET['create'] ?? null,
            $_GET['indexes'] ?? null,
            $_GET['foreign'] ?? null,
            $_GET['trigger'] ?? null,
            $_GET['check'] ?? null,
            $_GET['view'] ?? null,
        ], true);

        return '<a href="' . h(ME) . 'table=' . urlencode($name) . '"'
            . bold($isActive, is_view($status) ? 'view' : 'structure')
            . " title='" . h(lang('Show structure')) . "'>"
            . $displayName
            . '</a>';
    }

    /**
     * @param string[] $tableNames
     * @param string[] $viewNames
     * @param array{other: string, tables: string, views: string} $labels
     */
    private function prefixGroupsFormatCount(array $tableNames, array $viewNames, array $labels): string
    {
        $count = $labels['tables'] . ': ' . count($tableNames);

        if ($viewNames !== []) {
            $count .= ', ' . $labels['views'] . ': ' . count($viewNames);
        }

        return $count;
    }

    /**
     * @param array<string,mixed> $status
     */
    private function prefixGroupsIsHiddenTableStatus(array $status): bool
    {
        return !empty($status['partition']) || !empty($status['inherited']);
    }

    /**
     * @return array{other: string, tables: string, views: string}
     */
    private function prefixGroupsLabels(): array
    {
        $language = $this->prefixGroupsCurrentLanguage();
        $labels = $this->labelsByLanguage[$language]
            ?? $this->labelsByLanguage[strtok($language, '-')]
            ?? $this->labelsByLanguage['en'];

        return [
            'other' => $this->otherLabel ?? $labels['other'],
            'tables' => $this->tableCountLabel ?? $labels['tables'],
            'views' => $this->viewCountLabel ?? $labels['views'],
        ];
    }

    private function prefixGroupsCurrentLanguage(): string
    {
        $constant = __NAMESPACE__ . '\\LANG';

        if (defined($constant)) {
            return strtolower((string) constant($constant));
        }

        if (function_exists(__NAMESPACE__ . '\\get_lang')) {
            return strtolower((string) get_lang());
        }

        if (function_exists('get_lang')) {
            return strtolower((string) \get_lang());
        }

        return 'en';
    }

    /**
     * @param string[] $names
     */
    private function prefixGroupsContainsCurrentTable(array $names): bool
    {
        foreach ($this->prefixGroupsCurrentTableParams() as $name) {
            if ($name !== null && in_array($name, $names, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int,string|null>
     */
    private function prefixGroupsCurrentTableParams(): array
    {
        return [
            $_GET['select'] ?? null,
            $_GET['edit'] ?? null,
            $_GET['table'] ?? null,
            $_GET['create'] ?? null,
            $_GET['indexes'] ?? null,
            $_GET['foreign'] ?? null,
            $_GET['trigger'] ?? null,
            $_GET['check'] ?? null,
            $_GET['view'] ?? null,
        ];
    }

    private function prefixGroupsPrefixOf(string $name): ?string
    {
        if ($this->separator === '') {
            return null;
        }

        $position = strpos($name, $this->separator);

        if ($position === false || $position === 0) {
            return null;
        }

        return substr($name, 0, $position);
    }

    /**
     * @param array<string,array<string,mixed>> $tables
     * @param array<string,true> $usedNames
     * @return array{tables: string[], views: string[]}
     */
    private function prefixGroupsUngroupedNames(array $tables, array $usedNames): array
    {
        $ungroupedTables = [];
        $ungroupedViews = [];

        foreach ($tables as $name => $status) {
            if (isset($usedNames[$name])) {
                continue;
            }

            if (is_view($status)) {
                $ungroupedViews[] = (string) $name;
            } else {
                $ungroupedTables[] = (string) $name;
            }
        }

        return [
            'tables' => $this->prefixGroupsNaturalUnique($ungroupedTables),
            'views' => $this->prefixGroupsNaturalUnique($ungroupedViews),
        ];
    }

    /**
     * @param string[] $names
     * @return string[]
     */
    private function prefixGroupsNaturalUnique(array $names): array
    {
        $names = array_values(array_unique($names));
        natcasesort($names);

        return array_values($names);
    }
}
