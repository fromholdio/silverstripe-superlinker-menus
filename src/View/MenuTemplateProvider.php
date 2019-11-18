<?php

namespace Fromholdio\SuperLinkerMenus\View;

use Fromholdio\SuperLinkerMenus\Model\MenuSet;
use SilverStripe\Control\Controller;
use SilverStripe\View\TemplateGlobalProvider;

class MenuTemplateProvider implements TemplateGlobalProvider
{
    public static function get_template_global_variables()
    {
        return [
            'MenuSet' => 'MenuSet'
        ];
    }

    public static function MenuSet($key, $parentID = null)
    {
        if (!$parentID) {
            $curr = Controller::curr();
            if ($curr->hasMethod('getCurrentMenuSetParent')) {
                $parent = $curr->getCurrentMenuSetParent();
                if ($parent && $parent->exists()) {
                    return MenuSet::get_by_key($key, $parentID);
                }
            }
        }
        return MenuSet::get_by_key($key, $parentID);
    }
}
