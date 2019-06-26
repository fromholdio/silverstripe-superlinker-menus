<?php

namespace Fromholdio\SuperLinkerMenus\Model;

use Fromholdio\SuperLinker\Extensions\EmailLink;

class MenuItemEmail extends MenuItem
{
    private static $table_name = 'MenuItemEmail';

    private static $extensions = [
        EmailLink::class
    ];
}
