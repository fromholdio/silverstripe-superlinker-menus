<?php

namespace Fromholdio\SuperLinkerMenus\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\SiteConfig\SiteConfig;

class MenuSetParentAccessorExtension extends Extension
{
    public function getCurrentMenuSetParent()
    {
        $parent = ($multisitesClass = $this->getOwner()->getMultisitesClassName())
            ? $multisitesClass::inst()->getCurrentSite()
            : SiteConfig::current_site_config();
        $this->getOwner()->invokeWithExtensions('updateCurrentMenuSetParent', $parent);
        return $parent;
    }

    public function getMultisitesClassName(): ?string
    {
        $manifest = ModuleLoader::inst()->getManifest();
        if ($manifest->moduleExists('symbiote/silverstripe-multisites')) {
            return \Symbiote\Multisites\Multisites::class;
        }
        if ($manifest->moduleExists('fromholdio/silverstripe-configured-multisites')) {
            return \Fromholdio\ConfiguredMultisites\Multisites::class;
        }
        return null;
    }
}
