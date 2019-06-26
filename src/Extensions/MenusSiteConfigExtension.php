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
    private static $has_many = [
        'MenuSets' => MenuSet::class . '.Parent'
    ];

    private static $owns = [
        'MenuSets'
    ];

    private static $cascade_deletes = [
        'MenuSets'
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName('MenuSets');

        $fields->addFieldsToTab(
            'Root.Menus',
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

    public function onAfterWrite()
    {
        parent::onAfterWrite();
        MenuSet::update_menusets($this->getOwner());
    }
}
