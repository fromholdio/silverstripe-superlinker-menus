<?php

namespace Fromholdio\SuperLinkerMenus\Extensions;

use Fromholdio\SuperLinkerMenus\Model\MenuSet;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use Symbiote\Multisites\Model\Site;
use Symbiote\Multisites\Multisites;

class MultisitesMenuSetExtension extends DataExtension
{
    private static $has_one = [
        'Site' => Site::class
    ];

    public static function get_by_key_multisites($key, $siteID = null)
    {
        $site = Site::get()->byID($siteID);
        if (!$site || !$site->exists()) {
            $site = Multisites::inst()->getCurrentSite();
        }
        return $site->MenuSets()->find('Key', $key);
    }

    public static function update_menusets_multisites(Site $site = null)
    {
        if ($site) {
            $menuSets = MenuSet::update_menusets(
                $site->MenuSets(),
                $site->getSiteTheme()
            );
            if ($menuSets && $menuSets->count() > 0) {
                foreach ($menuSets as $menuSet) {
                    if ((int) $menuSet->SiteID !== (int) $site->ID) {
                        $menuSet->SiteID = $site->ID;
                        $menuSet->write();
                        $menuSet->publishSingle();
                    }
                }
            }
        }
        else {
            $sites = Site::get();
            foreach ($sites as $site) {
                MenuSet::update_menusets_multisites($site);
            }
        }
    }

    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName('SiteID');
    }

    public function updateCMSEditLink(&$link)
    {
        // TODO
    }
}
