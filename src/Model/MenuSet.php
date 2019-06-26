<?php

namespace Fromholdio\SuperLinkerMenus\Model;

use Fromholdio\GridFieldLimiter\Forms\GridFieldLimiter;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldPageCount;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Versioned\Versioned;
use SilverStripe\View\SSViewer;
use Symbiote\GridFieldExtensions\GridFieldAddNewMultiClass;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use Symbiote\Multisites\Model\Site;
use Symbiote\Multisites\Multisites;

class MenuSet extends DataObject implements PermissionProvider
{
    private static $table_name = 'MenuSet';
    private static $singular_name = 'Menu';
    private static $plural_name = 'Menus';

    private static $extensions = [
        Versioned::class
    ];

    private static $db = [
        'Name' => 'Varchar',
        'Title' => 'Varchar',
        'IsTitleEnabled' => 'Boolean',
        'Key' => 'Varchar(50)',
        'AllowedSubmenuCount' => 'Int',
        'Limit' => 'Int',
        'Sort' => 'Int'
    ];

    private static $has_one = [
        'Parent' => DataObject::class
    ];

    private static $has_many = [
        'Items' => MenuItem::class
    ];

    private static $owns = [
        'Items'
    ];

    private static $cascade_deletes = [
        'Items'
    ];

    private static $summary_fields = [
        'Name',
        'MenuItems.Count' => 'Items'
    ];

    private static $default_sort = 'Sort';

    public static function get_by_key($key, $siteID = null)
    {
        $config = null;

        $isMultisites = ModuleLoader::inst()
            ->getManifest()
            ->moduleExists('symbiote/silverstripe-multisites');

        if ($isMultisites) {

            $siteID = (int) $siteID;
            if ($siteID > 0) {
                $config = Site::get()->byID($siteID);
            }

            if (!$config || !$config->exists()) {
                $config = Multisites::inst()->getCurrentSite();
            }
        }

        if (!$config || !$config->exists()) {
            $config = SiteConfig::current_site_config();
        }

        return $config->MenuSets()->find('Key', $key);
    }

    public static function update_menusets($parent = null)
    {
        $isMultisites = ModuleLoader::inst()
            ->getManifest()
            ->moduleExists('symbiote/silverstripe-multisites');

        if (!$parent) {
            if ($isMultisites) {
                $sites = \Symbiote\Multisites\Model\Site::get();
                foreach ($sites as $site) {
                    self::update_menusets($site);
                }
                return null;
            }
            else {
                $siteConfig = SiteConfig::current_site_config();
                return self::update_menusets($siteConfig);
            }
        }

        if (is_a($parent, SiteConfig::class)) {
            $activeThemes = SSViewer::get_themes();
        }
        else if ($isMultisites && is_a($parent, 'Symbiote\Multisites\Model\Site')) {
            $activeThemes = $parent->getSiteTheme();
            if (!$activeThemes) {
                $activeThemes = SSViewer::get_themes();
            }
        }
        else {
            $activeThemes = [];
        }

        $menuConfigs = MenuSet::get_menu_configs($activeThemes);
        $menuSets = $parent->MenuSets();

        $existingMenuSetIDs = $menuSets->columnUnique('ID');
        $existingMenuSetIDs = array_combine($existingMenuSetIDs, $existingMenuSetIDs);

        foreach ($menuConfigs as $key => $options) {
            $menuSet = $parent->MenuSets()->filter('Key', $key)->first();
            if (!$menuSet || !$menuSet->exists()) {
                $menuSet = MenuSet::create();
                $menuSet->Key = $key;
            }
            $menuSet->Name = $options['name'];
            $menuSet->IsTitleEnabled = $options['title'];
            $menuSet->AllowedSubmenuCount = $options['allowedSubmenus'];
            $menuSet->Limit = $options['limit'];
            $menuSet->Sort = $options['sort'];
            $menuSet->ParentClass = $parent->ClassName;
            $menuSet->ParentID = $parent->ID;
            $menuSet->write();
            $menuSet->publishSingle();
            if (isset($existingMenuSetIDs[$menuSet->ID])) {
                unset($existingMenuSetIDs[$menuSet->ID]);
            }
        }

        foreach ($existingMenuSetIDs as $menuID) {
            $menuSet = $parent->MenuSets()->filter('ID', $menuID)->first();
            $menuSet->doUnpublish();
            $menuSet->doArchive();
        }
    }

    public static function get_menu_configs($themes = null)
    {
        $config = [];
        $menus = MenuSet::config()->get('menus');
        if (!$menus || !is_array($menus)) {
            return null;
        }

        if (isset($menus['themes'])) {
            $menuThemes = $menus['themes'];
            if ($themes) {
                if (!is_array($themes)) {
                    $themes = [$themes];
                }
                foreach ($themes as $theme) {
                    if (isset($menuThemes[$theme])) {
                        $themeMenus = $menuThemes[$theme];
                        foreach ($themeMenus as $key => $options) {
                            $key = Convert::raw2htmlid($key);
                            $config[$key] = self::parse_menu_config($options);
                        }
                    }
                }
            }
            unset($menus['themes']);
        }

        foreach ($menus as $key => $options) {
            $key = Convert::raw2htmlid($key);
            $config[$key] = self::parse_menu_config($options);
        }

        return $config;
    }

    public static function parse_menu_config($config)
    {
        if (is_array($config)) {
            $name = $config['name'];
            if (isset($config['allowed_submenus'])) {
                $allowedSubmenus = (int) $config['allowed_submenus'];
            }
            else {
                $allowedSubmenus = 0;
            }
            if (isset($config['limit'])) {
                $limit = (int) $config['limit'];
            }
            else {
                $limit = 0;
            }
            if (isset($config['sort'])) {
                $sort = (int) $config['sort'];
            }
            else {
                $sort = 0;
            }
            if (isset($config['title'])) {
                $title = (bool) $config['title'];
            }
            else {
                $title = false;
            }
        }
        else {
            $name = $config;
            $title = false;
            $allowedSubmenus = 0;
            $limit = 0;
            $sort = 0;
        }

        return [
            'name' => $name,
            'title' => $title,
            'allowedSubmenus' => $allowedSubmenus,
            'limit' => $limit,
            'sort' => $sort
        ];
    }

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        self::update_menusets();
    }

    public function getCMSFields()
    {
        $fields = FieldList::create(
            TabSet::create(
                'Root',
                Tab::create('Main')
            )
        );

        $fields->addFieldsToTab(
            'Root.Main',
            [
                ReadonlyField::create('Name'),
                GridField::create(
                    'Items',
                    'Menu Items',
                    $this->Items(),
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
            ->addComponent(new GridFieldOrderableRows());

        if ($this->AllowedSubmenuCount > 0) {
            $fields->insertAfter(
                'Name',
                ReadonlyField::create('AllowedSubmenuCount')
            );
        }

        $adder = new GridFieldAddNewMultiClass();
        $adder->setClasses($this->getMenuItemClasses());

        if ($this->IsTitleEnabled) {
            $titleField = TextField::create(
                'Title',
                $this->fieldLabel('Title')
            );
            $fields->insertAfter('Name', $titleField);
        }

        if ($this->Limit > 0) {
            $config->addComponent(
                new GridFieldLimiter($this->Limit, 'before', true)
            );
            $adder->setFragment('limiter-before-left');
            $fields->insertAfter(
                'Name',
                ReadonlyField::create('Limit')
            );
        }
        $config->addComponent($adder);

        $this->extend('updateCMSFields', $fields);
        return $fields;
    }

    public function MenuItems($includeChildren = false)
    {
        return $this->getMenuItems($includeChildren);
    }

    public function getMenuItems($includeChildren = false)
    {
        $items = $this->Items();
        if ($includeChildren) {
            return $items;
        }
        return $items->filter('ParentID', 0);
    }


    public function getMenuItemClasses()
    {
        $list = [];
        $classes = ClassInfo::subclassesFor(MenuItem::class);
        foreach ($classes as $class) {
            $list[$class] = $class::singleton()->getMultiAddTitle();
        }
        asort($list);
        if (array_key_exists(MenuItem::class, $list)) {
            unset($list[MenuItem::class]);
        }
        return $list;
    }

    public function PermissionKey()
    {
        return 'MENUS_EDIT_' . $this->obj('Key')->Uppercase();
    }

    public function providePermissions()
    {
        $permissions = [];
        foreach (MenuSet::get() as $menuSet) {
            $key = $menuSet->PermissionKey();
            $permissions[$key] = [
                'name' => 'Manage ' . $menuSet->obj('Name'),
                'category' => 'Menus'
            ];
        }
        return $permissions;
    }

    public function canCreate($member = null, $context = null)
    {
        return false;
    }

    public function canDelete($member = null)
    {
        return false;
    }

    public function canEdit($member = null)
    {
        return Permission::check($this->PermissionKey(), 'any', $member);
    }

    public function canView($member = null)
    {
        return Permission::check($this->PermissionKey(), 'any', $member);
    }
}
