<?php

namespace Fromholdio\SuperLinkerMenus\Extensions;

use Fromholdio\SuperLinkerMenus\Model\MenuSet;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldPageCount;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
use SilverStripe\ORM\DataExtension;

class MenusSiteConfigExtension extends DataExtension
{
    private static $menus_tab_path = 'Root.Menus';

    public function updateCMSFields(FieldList $fields)
    {
        $tabPath = $this->getMenusTabPath();
        if (!$tabPath) {
            return;
        }

        $fields->addFieldsToTab(
            $tabPath,
            [
                GridField::create(
                    'MenuSets',
                    'Menus',
                    MenuSet::get(),
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
