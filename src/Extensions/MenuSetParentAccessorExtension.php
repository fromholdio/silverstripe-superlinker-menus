<?php

namespace Fromholdio\SuperLinkerMenus\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\SiteConfig\SiteConfig;
use Symbiote\Multisites\Multisites;

class MenuSetParentAccessorExtension extends Extension
{
    public function getCurrentMenuSetParent()
    {
        $isMultisites = ModuleLoader::inst()
            ->getManifest()
            ->moduleExists('symbiote/silverstripe-multisites');
        $parent = $isMultisites
            ? Multisites::inst()->getCurrentSite()
            : SiteConfig::current_site_config();
        $this->getOwner()->invokeWithExtensions('updateCurrentMenuSetParent', $parent);
        return $parent;
    }
}
