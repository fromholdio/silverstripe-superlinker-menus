<?php

namespace Fromholdio\SuperLinkerMenus\Model;

use Fromholdio\Sortable\Extensions\Sortable;
use Fromholdio\SuperLinker\Model\SuperLink;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldPageCount;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Versioned\Versioned;
use Symbiote\GridFieldExtensions\GridFieldAddNewMultiClass;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

class MenuItem extends SuperLink
{
    private static $table_name = 'MenuItem';
    private static $singular_name = 'Menu Item';
    private static $plural_name = 'Menu Items';

    private static $allow_anchor = true;
    private static $allow_query_string = true;

    private static $extensions = [
        Sortable::class,
        Versioned::class
    ];

    private static $has_one = [
        'MenuSet' => MenuSet::class,
        'Parent' => MenuItem::class
    ];

    private static $has_many = [
        'Children' => MenuItem::class
    ];

    private static $owns = [
        'Children'
    ];

    private static $cascade_deletes = [
        'Children'
    ];

    private static $summary_fields = [
        'Title' => 'Link',
        'Link' => 'URL'
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName([
            'Children',
            'MenuSetID',
            'ParentID'
        ]);

        if (!$this->isAllowedChildren()) {
            return $fields;
        }

        if ($this->isInDB()) {
            $fields->addFieldsToTab(
                'Root.Main',
                [
                    LiteralField::create('GridFieldPadding', '<br>'),
                    GridField::create(
                        'Children',
                        'Child Menu Items',
                        $this->Children(),
                        $config = GridFieldConfig_RecordEditor::create()
                    )
                ]
            );

            $config
                ->removeComponentsByType([
                    GridFieldAddNewButton::class,
                    GridFieldAddExistingAutocompleter::class,
                    GridFieldPageCount::class,
                    GridFieldPaginator::class,
                    GridFieldToolbarHeader::class
                ])
                ->addComponents([
                    new GridFieldOrderableRows(),
                    $adder = new GridFieldAddNewMultiClass()
                ]);

            $adder->setClasses($this->MenuSet()->getMenuItemClasses());
        }

        return $fields;
    }

    public function isAllowedChildren()
    {
        $maxSubmenus = $this->MenuSet()->AllowedSubmenuCount;
        if ($maxSubmenus < 1) {
            return false;
        }

        if ($this->ParentID < 1) {
            return true;
        }

        $parent = $this->Parent();

        $i = 1;
        while ($i <= $maxSubmenus) {
            if ($parent->ParentID < 1) {
                return true;
            }
            $parent = $parent->Parent();
            $i++;
        }

        return false;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if ($this->ParentID) {
            $this->MenuSetID = $this->Parent()->MenuSetID;
        }
    }

    public function getSortableScope()
    {
        return self::get()
            ->filter([
                'MenuSetID' => $this->MenuSetID,
                'ParentID' => $this->ParentID
            ])
            ->exclude('ID', $this->ID);
    }

    public function canView($member = null)
    {
        return $this->MenuSet()->canView($member);
    }

    public function canEdit($member = null)
    {
        return $this->MenuSet()->canEdit($member);
    }

    public function canDelete($member = null)
    {
        return $this->MenuSet()->canDelete($member);
    }

    public function canCreate($member = null, $context = [])
    {
        if (isset($context['Parent'])) {
            return $context['Parent']->canEdit($member);
        }

        return $this->MenuSet()->canEdit($member);
    }
}
