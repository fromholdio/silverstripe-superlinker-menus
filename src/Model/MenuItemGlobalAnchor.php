<?php

namespace Fromholdio\SuperLinkerMenus\Model;

use Fromholdio\SuperLinker\Extensions\GlobalAnchorLink;

class MenuItemGlobalAnchor extends MenuItem
{
    private static $table_name = 'MenuItemGlobalAnchor';

    private static $extensions = [
        GlobalAnchorLink::class
    ];
}
