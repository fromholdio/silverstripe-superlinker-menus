<?php

namespace Fromholdio\SuperLinkerMenus\Model;

use Fromholdio\SuperLinker\Extensions\FileLink;

class MenuItemFile extends MenuItem
{
    private static $table_name = 'MenuItemFile';

    private static $extensions = [
        FileLink::class
    ];
}
