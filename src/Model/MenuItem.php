<?php

namespace Fromholdio\SuperLinkerMenus\Model;

use Fromholdio\Sortable\Extensions\Sortable;
use Fromholdio\SuperLinker\Model\SuperLink;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldPageCount;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use UncleCheese\DisplayLogic\Forms\Wrapper;

class MenuItem extends SuperLink implements PermissionProvider
{
    const SUBMENU_SITETREE = 'sitetree';
    const SUBMENU_MANUAL = 'manual';
    const SUBMENU_NONE = 'none';

    private static $table_name = 'MenuItem';
    private static $singular_name = 'Menu Item';
    private static $plural_name = 'Menu Items';

    private static $enable_tabs = true;

    private static $submenu_mode_options = [
        self::SUBMENU_NONE => 'No submenu',
        self::SUBMENU_SITETREE => 'Build submenu from links to children of a page on this site',
        self::SUBMENU_MANUAL => 'Manually build a submenu of links'
    ];

    private static $extensions = [
        Sortable::class,
    ];

    private static $db = [
        'SubmenuMode' => 'Varchar(20)',
        'IsHighlighted' => 'Boolean'
    ];

    private static $has_one = [
        'MenuSet' => MenuSet::class,
        'Parent' => MenuItem::class,
        'SubmenuSiteTree' => SiteTree::class
    ];

    private static $has_many = [
        'Children' => MenuItem::class . '.Parent'
    ];

    private static $owns = [
        'Children'
    ];

    private static $cascade_deletes = [
        'Children'
    ];

    private static $cascade_duplicates = [
        'Children'
    ];

    private static $field_labels = [
        'SubmenuMode' => 'Submenu',
        'SubmenuSiteTree' => 'Select Page'
    ];

    private static $summary_fields = [
        'Title' => 'Title',
        'URL' => 'URL'
    ];

    public function getCMSFields(): FieldList
    {
        $fields = parent::getCMSFields();

        $fields->removeByName([
            'Children',
            'MenuSetID',
            'ParentID',
            'SubmenuMode',
            'SubmenuSiteTreeID'
        ]);


        if ($this->getCanBeHighlighted())
        {
            $highlightField = FieldGroup::create(
                CheckboxField::create(
                    'IsHighlighted',
                    'Highlight this menu item'
                )
            );
            $highlightField->setTitle('Style');
            $highlightField->setName('IsHighlightedGroup');
            $fields->addFieldToTab(
                'Root.Main',
                $highlightField
            );
        }

        if (!$this->getCanHaveChildren()) {
            return $fields;
        }

        $modeOptions = $this->getSubmenuModeOptions();
        if (!$modeOptions) {
            return $fields;
        }

        $submenuTab = $fields->findOrMakeTab('Root.Submenu');

        if (!$this->isInDB()) {
            $submenuTab->push(
                HeaderField::create(
                    'SubmenuHeader',
                    'You can construct a submenu after saving this menu item for the first time.',
                    3
                )
            );
            return $fields;
        }

        if (!$this->SubmenuMode) {
            $this->SubmenuMode = $this->getDefaultSubmenuMode();
        }

        $modeField = OptionsetField::create(
            'SubmenuMode',
            $this->fieldLabel('SubmenuMode'),
            $modeOptions
        );
        $submenuTab->push($modeField);

        if (isset($modeOptions[self::SUBMENU_SITETREE])) {

            $submenuSiteTreeField = TreeDropdownField::create(
                'SubmenuSiteTreeID',
                $this->fieldLabel('SubmenuSiteTree'),
                SiteTree::class
            );
            $submenuSiteTreeWrapper = Wrapper::create($submenuSiteTreeField);
            $submenuSiteTreeWrapper->displayIf('SubmenuMode')->isEqualTo(self::SUBMENU_SITETREE);
            $submenuTab->push($submenuSiteTreeWrapper);
        }

        if (isset($modeOptions[self::SUBMENU_MANUAL])) {

            $childrenField = GridField::create(
                'Children',
                $this->fieldLabel('Children'),
                $this->Children(),
                $childrenConfig = GridFieldConfig_RecordEditor::create()
            );

            $childrenConfig
                ->removeComponentsByType([
                    GridFieldAddExistingAutocompleter::class,
                    GridFieldPageCount::class,
                    GridFieldPaginator::class,
                    GridFieldToolbarHeader::class
                ])
                ->addComponents([
                    new GridFieldOrderableRows(),
                ]);

            $childrenWrapper = Wrapper::create($childrenField);
            $childrenWrapper->displayIf('SubmenuMode')->isEqualTo(self::SUBMENU_MANUAL);
            $submenuTab->push($childrenWrapper);
        }

        $this->extend('updateMenuItemCMSFields', $fields);
        return $fields;
    }

    public function getCanBeHighlighted()
    {
        $menuSet = $this->getRootMenuSet();
        if (!$menuSet) {
            return false;
        }
        return (bool) $menuSet->IsHighlightEnabled;
    }

    public function getCanHaveChildren()
    {
        $menuSet = $this->getRootMenuSet();
        if (!$menuSet) {
            return false;
        }
        $maxDepth = (int) $menuSet->MaxDepth;
        if ($maxDepth < 1) {
            return false;
        }

        $depth = $this->getDepth();
        if ($depth < 1) {
            return true;
        }

        return ($maxDepth > $depth);
    }

    public function getDepth()
    {
        $parentID = (int) $this->ParentID;
        if (!$parentID) {
            return 0;
        }

        $i = 1;
        $parent = $this;
        while($parentID > 0) {
            $parent = $parent->Parent();
            $parentID = (int) $parent->ParentID;
            $i++;
        }
        return $i;
    }

    public function getDefaultSubmenuMode()
    {
        $mode = self::SUBMENU_NONE;
        $this->extend('updateDefaultSubmenuMode', $mode);
        return $mode;
    }

    public function getSubmenuModeOptions()
    {
        $options = $this->config()->get('submenu_mode_options');
        if (!$this->getCanHaveChildren()) {
            $options = null;
        }
        foreach ($options as $key => $label) {
            if (!$label) {
                unset($options[$key]);
            }
        }
        $this->extend('updateSubmenuModeOptions', $options);
        if (!$options || !is_array($options)) {
            $options = null;
        }
        else if (count($options) === 1 && isset($options[self::SUBMENU_NONE])) {
            $options = null;
        }
        return $options;
    }

    public function getRootMenuSet()
    {
        if ($this->MenuSetID) {
            return $this->MenuSet();
        }
        if ($this->ParentID) {
            return $this->Parent()->getRootMenuSet();
        }
        return null;
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

    public function CMSEditLink()
    {
        $link = null;
        if ($this->ParentID) {
            $link = $this->Parent()->CMSEditLink();
            $link = preg_replace('/\/item\/([\d]+)\/edit/', '/item/$1', $link);
            $link = Controller::join_links(
                $link,
                'ItemEditForm/field/Children/item',
                $this->ID,
                'edit'
            );
        }
        else if ($this->MenuSetID) {
            $link = $this->MenuSet()->CMSEditLink();
            $link = preg_replace('/\/item\/([\d]+)\/edit/', '/item/$1', $link);
            $link = Controller::join_links(
                $link,
                'ItemEditForm/field/Items/item',
                $this->ID,
                'edit'
            );
        }
        $this->extend('updateCMSEditLink', $link);
        return $link;
    }

    public function canView($member = null)
    {
        $can = Permission::checkMember($member, 'MANAGE_MENUITEMS');
        if ($can) {
            if ($this->ParentID) {
                $can = $this->Parent()->canView($member, $context);
            }
        }
        return $can;
    }

    public function canEdit($member = null)
    {
        $can = Permission::checkMember($member, 'MANAGE_MENUITEMS');
        if ($can) {
            if ($this->ParentID) {
                $can = $this->Parent()->canEdit($member, $context);
            }
        }
        return $can;
    }

    public function canDelete($member = null)
    {
        $can = Permission::checkMember($member, 'DELETE_MENUITEMS');
        if ($can) {
            if ($this->ParentID) {
                $can = $this->Parent()->canDelete($member, $context);
            }
        }
        return $can;
    }

    public function canCreate($member = null, $context = [])
    {
        $can = Permission::checkMember($member, 'CREATE_MENUITEMS');
        if ($can) {
            if ($this->ParentID) {
                $can = $this->Parent()->canCreate($member, $context);
            }
        }
        return $can;
    }

    public function providePermissions() {
        return [
            'MANAGE_MENUITEMS' => array(
                'name' => 'Manage menu items',
                'category' => 'Menus',
            ),
            'CREATE_MENUITEMS' => array(
                'name' => 'Create menu items',
                'category' => 'Menus',
            ),
            'DELETE_MENUITEMS' => array(
                'name' => 'Delete menu items',
                'category' => 'Menus',
            )
        ];
    }
}
