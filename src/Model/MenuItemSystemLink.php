<?php

namespace Fromholdio\SuperLinkerMenus\Model;

use Fromholdio\SuperLinker\Extensions\SystemLink;

class MenuItemSystemLink extends MenuItem
{
    private static $table_name = 'MenuItemSystemLink';

    private static $extensions = [
        SystemLink::class
    ];
}
