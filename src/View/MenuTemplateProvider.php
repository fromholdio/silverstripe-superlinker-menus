<?php

namespace Fromholdio\SuperLinkerMenus\View;

use Fromholdio\SuperLinkerMenus\Model\MenuSet;
use SilverStripe\View\TemplateGlobalProvider;

class MenuTemplateProvider implements TemplateGlobalProvider
{
    public static function get_template_global_variables()
    {
        return [
            'MenuSet' => 'MenuSet'
        ];
    }

    public static function MenuSet($key)
    {
        return MenuSet::get_by_key($key);
    }
}
