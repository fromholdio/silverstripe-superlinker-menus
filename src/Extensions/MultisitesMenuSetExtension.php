<?php

namespace Fromholdio\SuperLinkerMenus\Extensions;

use Fromholdio\SuperLinkerMenus\Model\MenuSet;
use SilverStripe\CMS\Controllers\CMSPageEditController;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use Symbiote\Multisites\Model\Site;
use Symbiote\Multisites\Multisites;

class MultisitesMenuSetExtension extends DataExtension
{
    private static $has_one = [
        'Site' => Site::class
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName('SiteID');
    }

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
                self::update_menusets_multisites($site);
            }
        }
    }

    public function updateCMSEditLink(&$link)
    {
        $site = $this->getOwner()->Site();
        if ($site) {
            $link = Controller::join_links(
                singleton(CMSPageEditController::class)->Link('EditForm'),
                $site->ID,
                'field/MenuSets/item',
                $this->getOwner()->ID
            );
        }
        else {
            $link = null;
        }
    }
}
