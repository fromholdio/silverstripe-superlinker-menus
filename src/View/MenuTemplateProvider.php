<?php

namespace Fromholdio\SuperLinkerMenus\View;

use Fromholdio\SuperLinkerMenus\Model\MenuSet;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\View\TemplateGlobalProvider;

class MenuTemplateProvider implements TemplateGlobalProvider
{
    public static function get_template_global_variables()
    {
        return [
            'MenuSet' => 'MenuSet'
        ];
    }

    public static function MenuSet($key, $siteID = null)
    {
        $isMultisites = ModuleLoader::inst()->getManifest()
            ->moduleExists('symbiote/silverstripe-multisites');

        if ($isMultisites) {
            return MenuSet::get_by_key_multisites($key, $siteID);
        }
        return MenuSet::get_by_key($key);
    }
}
