<?php

namespace Fromholdio\SuperLinkerMenus\Extensions;

use Fromholdio\SuperLinkerMenus\Model\MenuSet;
use SilverStripe\CMS\Model\SiteTreeExtension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldPageCount;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;

class MenusSiteExtension extends SiteTreeExtension
{
    private static $menus_tab_path = 'Root.Menus';

    private static $has_many = [
        'MenuSets' => MenuSet::class
    ];

    private static $owns = [
        'MenuSets'
    ];

    private static $cascade_deletes = [
        'MenuSets'
    ];

    public function updateSiteCMSFields(FieldList $fields)
    {
        $fields->removeByName('MenuSets');

        $tabPath = $this->getMenuSetsTabPath();
        if (!$tabPath) {
            return;
        }

        $fields->addFieldsToTab(
            $tabPath,
            [
                GridField::create(
                    'MenuSets',
                    'Menus',
                    $this->getOwner()->MenuSets(),
                    $config = GridFieldConfig_RecordEditor::create()
                )
            ]
        );

        $config->removeComponentsByType([
            GridFieldAddNewButton::class,
            GridFieldAddExistingAutocompleter::class,
            GridFieldPageCount::class,
            GridFieldPaginator::class,
            GridFieldToolbarHeader::class
        ]);
    }

    public function getMenusTabPath()
    {
        $path = $this->getOwner()->config()->get('menus_tab_path');
        $this->getOwner()->invokeWithExtensions('updateMenusTabPath');
        return $path;
    }
}
