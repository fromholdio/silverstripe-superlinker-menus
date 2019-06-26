<?php

namespace Fromholdio\SuperLinkerMenus\Model;

use Fromholdio\SuperLinker\Extensions\ExternalURLLink;

class MenuItemExternalURL extends MenuItem
{
    private static $table_name = 'MenuItemExternalURL';

    private static $extensions = [
        ExternalURLLink::class
    ];
}
