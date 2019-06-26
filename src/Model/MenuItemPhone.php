<?php

namespace Fromholdio\SuperLinkerMenus\Model;

use Fromholdio\SuperLinker\Extensions\PhoneLink;

class MenuItemPhone extends MenuItem
{
    private static $table_name = 'MenuItemPhone';

    private static $extensions = [
        PhoneLink::class
    ];
}
