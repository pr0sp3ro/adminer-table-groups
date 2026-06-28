<?php
declare(strict_types=1);

namespace Adminer {
    if (!class_exists(__NAMESPACE__ . '\\Plugin')) {
        abstract class Plugin
        {
        }
    }
}

namespace {
    require_once __DIR__ . '/../table-prefix-groups.php';

    return new Adminer\AdminerTablePrefixGroups();
}
