<?php
declare(strict_types=1);

namespace Adminer;

/** Organize sidebar tables using browser-local, user-defined groups. */
class AdminerTableGroups extends Plugin
{
    public function __construct(
        private string $cssFile = 'adminer-plugins/assets/table-groups.css',
        private ?string $otherLabel = null,
        private ?string $tableCountLabel = null,
        private ?string $viewCountLabel = null,
        private bool $useSelectIcon = true,
        private string $jsFile = 'adminer-plugins/assets/table-groups.js',
    ) {
    }

    public function head(?bool $dark = null): void
    {
        $cssVersion = crc32((string) file_get_contents(__DIR__ . '/assets/table-groups.css'));
        $jsVersion  = crc32((string) file_get_contents(__DIR__ . '/assets/table-groups.js'));

        echo '<link rel="stylesheet" href="' . h($this->cssFile . '?v=' . $cssVersion) . '">' . "\n";
        echo script_src($this->jsFile . '?v=' . $jsVersion, true);
    }

    public function tablesPrint(array $tables): ?bool
    {
        $labels = $this->groupsLabels();
        $config = [
            'context' => [
                DRIVER,
                SERVER,
                (string) ($_GET['username'] ?? ''),
                DB,
                (string) ($_GET['ns'] ?? ''),
            ],
            'labels'  => $labels,
        ];

        echo '<ul id="tables" class="table-groups" data-groups-config="'
            . h((string) json_encode($config, JSON_INVALID_UTF8_SUBSTITUTE)) . '"'
            . on('mouseover', 'menuOver') . on('mouseout', 'menuOut') . '>';

        foreach ($tables as $name => $status) {
            $displayName = adminer()->tableName($status);

            if ($displayName === '' || !empty($status['dependent']) || !empty($status['partition']) || !empty($status['inherited'])) {
                continue;
            }

            $name = (string) $name;

            echo '<li data-table-name="' . h($name) . '" data-view="' . (is_view($status) ? '1' : '0') . '">';
            echo $this->groupsRenderSelectLink($name) . ' ';
            echo $this->groupsRenderStructureLink($name, $status, $displayName);
            echo "</li>\n";
        }

        echo "</ul>\n";

        return true;
    }

    private function groupsRenderSelectLink(string $name): string
    {
        $isActive = ($_GET['select'] ?? null) === $name || ($_GET['edit'] ?? null) === $name;

        if (!$this->useSelectIcon) {
            return '<a href="' . h(ME) . 'select=' . urlencode($name) . '"'
                . bold($isActive, 'select hover')
                . " title='" . h(lang(50)) . "'>"
                . h(lang(89))
                . '</a>';
        }

        return '<a href="' . h(ME) . 'select=' . urlencode($name) . '"'
            . bold($isActive, 'select hover table-groups-select-link')
            . " title='" . h(lang(50)) . "'"
            . " aria-label='" . h(lang(50)) . "'>"
            . '<svg class="table-groups-eye" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false">'
            . '<path fill="currentColor" d="M12 5c-5 0-9 5-10 7 1 2 5 7 10 7s9-5 10-7c-1-2-5-7-10-7Zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Zm0-2a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/>'
            . '</svg>'
            . '<span class="table-groups-select-text">' . h(lang(89)) . '</span>'
            . '</a>';
    }

    /**
     * @param array<string,mixed> $status
     */
    private function groupsRenderStructureLink(string $name, array $status, string $displayName): string
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
            . " title='" . h(lang(51)) . "'>"
            . $displayName
            . '</a>';
    }

    /** @return array<string,string> */
    private function groupsLabels(): array
    {
        $translations = [
            'en' => [
                'other'               => 'Other',
                'tables'              => 'tables',
                'views'               => 'views',
                'manage'              => 'Manage groups',
                'newGroup'            => 'New group',
                'create'              => 'Create',
                'rename'              => 'Rename',
                'delete'              => 'Delete',
                'groupName'           => 'Group name',
                'noGroup'             => 'No group',
                'invalidName'         => 'Enter a unique, non-empty group name.',
                'invalidStorage'      => 'Saved groups are invalid. Tables are shown without assignments.',
                'storageUnavailable'  => 'Changes cannot be saved. They will be lost when this page is reloaded.',
                'confirmDelete'       => 'Delete this group and move its objects to Other?',
            ],
            'pl' => [
                'other'               => 'Pozostałe',
                'tables'              => 'tab.',
                'views'               => 'widoki',
                'manage'              => 'Zarządzaj grupami',
                'newGroup'            => 'Nowa grupa',
                'create'              => 'Utwórz',
                'rename'              => 'Zmień nazwę',
                'delete'              => 'Usuń',
                'groupName'           => 'Nazwa grupy',
                'noGroup'             => 'Bez grupy',
                'invalidName'         => 'Podaj niepustą, unikalną nazwę grupy.',
                'invalidStorage'      => 'Zapis grup jest niepoprawny. Tabele są wyświetlane bez przypisań.',
                'storageUnavailable'  => 'Nie można zapisać zmian. Zostaną utracone po przeładowaniu strony.',
                'confirmDelete'       => 'Usunąć grupę i przenieść jej obiekty do Pozostałe?',
            ],
            'uk' => [
                'other'               => 'Інші',
                'tables'              => 'таблиці',
                'views'               => 'подання',
                'manage'              => 'Керування групами',
                'newGroup'            => 'Нова група',
                'create'              => 'Створити',
                'rename'              => 'Перейменувати',
                'delete'              => 'Видалити',
                'groupName'           => 'Назва групи',
                'noGroup'             => 'Без групи',
                'invalidName'         => 'Введіть непорожню, унікальну назву групи.',
                'invalidStorage'      => 'Збережені групи недійсні. Таблиці показано без призначень.',
                'storageUnavailable'  => 'Неможливо зберегти зміни. Вони будуть втрачені після перезавантаження сторінки.',
                'confirmDelete'       => 'Видалити групу та перемістити її об’єкти до Інші?',
            ],
        ];

        $language = strtolower(str_replace('_', '-', LANG));
        $labels   = $translations[explode('-', $language)[0]] ?? $translations['en'];

        $labels['other']  = $this->otherLabel ?? $labels['other'];
        $labels['tables'] = $this->tableCountLabel ?? $labels['tables'];
        $labels['views']  = $this->viewCountLabel ?? $labels['views'];

        return $labels;
    }
}
