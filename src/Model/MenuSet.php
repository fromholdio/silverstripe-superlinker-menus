<?php

namespace Fromholdio\SuperLinkerMenus\Model;

use Fromholdio\GridFieldLimiter\Forms\GridFieldLimiter;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Control\Controller;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldPageCount;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataList;
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
        'Key' => 'Varchar(50)',
        'Name' => 'Varchar',
        'CustomTitle' => 'Varchar',
        'IsTitleEnabled' => 'Boolean',
        'ItemsLimit' => 'Int',
        'MaxDepth' => 'Int',
        'Sort' => 'Int'
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

    private static $defaults = [
        'IsTitleEnabled' => false,
        'ItemsLimit' => 0,
        'MaxDepth' => 0,
        'Sort' => 1
    ];

    private static $summary_fields = [
        'Title',
        'Items.Count' => 'Items'
    ];

    private static $default_sort = 'Sort';

    public function MenuItems()
    {
        return $this->Items();
    }

    public static function get_by_key($key)
    {
        return MenuSet::get()->find('Key', $key);
    }

    public static function update_menusets(DataList $menuSets = null, $themes = null)
    {
        if (is_null($menuSets)) {
            $menuSets = self::get();
        }
        if (!$themes) {
            $themes = SSViewer::get_themes();
        }

        $updatedMenuSetIDs = [];

        $configMenuSets = self::get_config_menusets($themes);
        if ($configMenuSets) {
            foreach ($menuSets as $menuSet) {
                if (isset($configMenuSets[$menuSet->Key])) {
                    $data = $configMenuSets[$menuSet->Key];
                    $menuSet = $menuSet->updateFromConfig($data);
                    $updatedMenuSetIDs[] = $menuSet->ID;
                    unset($configMenuSets[$menuSet->Key]);
                }
                else {
                    $menuSet->doArchive();
                }
            }

            foreach ($configMenuSets as $key => $data) {
                $menuSet = self::create();
                $menuSet->Key = $key;
                $menuSet = $menuSet->updateFromConfig($data);
                $updatedMenuSetIDs[] = $menuSet->ID;
            }
        }
        else {
            if ($menuSets->count() > 0) {
                foreach ($menuSets as $menuSet) {
                    $menuSet->doArchive();
                }
            }
        }

        if (count($updatedMenuSetIDs) < 1) {
            return null;
        }

        return self::get()->filter('ID', $updatedMenuSetIDs);
    }

    public static function get_config_menusets($themes = null)
    {
        $menuSets = [];

        $config = Config::inst()->get(self::class, 'theme_menus');
        if (is_array($config)) {
            if ($themes) {
                if (!is_array($themes)) {
                    $themes = [$themes];
                }
                foreach ($themes as $theme) {
                    if (isset($config[$theme])) {
                        foreach ($config[$theme] as $key => $data) {
                            $menuSets[$key] = $data;
                        }
                    }
                }
            }
        }
        else {
            $config = Config::inst()->get(self::class, 'menus');
            if (is_array($config)) {
                foreach ($config as $key => $data) {
                    $menuSets[$key] = $data;
                }
            }
        }

        if (count($menuSets) < 1) {
            return null;
        }
        return $menuSets;
    }

    public function getTitle()
    {
        $title = null;
        if ($this->IsTitleEnabled && $this->CustomTitle) {
            $title = $this->CustomTitle;
        }
        $curr = Controller::curr();
        if (is_a($curr, LeftAndMain::class)) {
            if ($title) {
                $title .= ' (' . $this->Name . ')';
            }
            else {
                $title = $this->Name;
            }
        }
        $this->extend('updateTitle', $title);
        return $title;
    }

    public function updateFromConfig($data)
    {
        if (is_string($data)) {
            if ($data !== $this->Name) {
                $this->Name = $data;
                $this->write();
                $this->publishSingle();
            }
            return $this;
        }

        $doWrite = false;

        if ($data['name'] !== $this->Name) {
            $this->Name = $data['name'];
            $doWrite = true;
        }

        if (isset($data['enable_title'])) {
            $isTitleEnabled = (bool) $data['enable_title'];
            if ($isTitleEnabled && !$this->IsTitleEnabled) {
                $this->IsTitleEnabled = true;
                $doWrite = true;
            }
            else if (!$isTitleEnabled && $this->IsTitleEnabled) {
                $this->IsTitleEnabled = false;
                $doWrite = true;
            }
        }

        if (isset($data['limit'])) {
            $limit = (int) $data['limit'];
            if ($limit !== (int) $this->Limit) {
                $this->Limit = $limit;
                $doWrite = true;
            }
        }

        if (isset($data['max_depth'])) {
            $maxDepth = (int) $data['max_depth'];
            if ($maxDepth !== (int) $this->MaxDepth) {
                $this->MaxDepth = $maxDepth;
                $doWrite = true;
            }
        }

        if (isset($data['sort'])) {
            $sort = (int) $data['sort'];
            if ($sort !== (int) $this->Sort) {
                $this->Sort = $sort;
                $doWrite = true;
            }
        }

        if ($doWrite) {
            $this->write();
            $this->publishSingle();
        }

        return $this;
    }

    public function requireDefaultRecords()
    {
        $isMultisites = ModuleLoader::inst()
            ->getManifest()
            ->moduleExists('symbiote/silverstripe-multisites');

        if ($isMultisites) {
            self::update_menusets_multisites();
        }
        else {
            self::update_menusets();
        }
    }

    public function getCMSFields()
    {
        $fields = FieldList::create(
            TabSet::create(
                'Root',
                $mainTab = Tab::create(
                    'Main',
                    HeaderField::create('NameHeader', $this->Name, 2)
                )
            )
        );

        if ($this->IsTitleEnabled) {
            $mainTab->push(
                TextField::create('Title', $this->fieldLabel('Title'))
            );
        }

        $itemsField = GridField::create(
            'Items',
            $this->fieldLabel('Items'),
            $this->Items(),
            $itemsConfig = GridFieldConfig_RecordEditor::create()
        );

        $itemsConfig
            ->removeComponentsByType([
                GridFieldAddNewButton::class,
                GridFieldAddExistingAutocompleter::class,
                GridFieldPageCount::class,
                GridFieldPaginator::class,
                GridFieldToolbarHeader::class
            ])
            ->addComponent(new GridFieldOrderableRows());

        $itemsAdder = new GridFieldAddNewMultiClass();
        $itemsAdder->setClasses($this->getMenuItemClasses());

        if ($this->Limit > 0) {
            $itemsLimiter = new GridFieldLimiter($this->Limit, 'before', true);
            $itemsConfig->addComponent($itemsLimiter);
            $itemsAdder->setFragment('limiter-before-left');
        }

        $itemsConfig->addComponent($itemsAdder);

        $mainTab->push($itemsField);
        $mainTab->push(HiddenField::create('ID', false));

        $this->extend('updateCMSFields', $fields);
        return $fields;
    }

    public function validate()
    {
        $result = parent::validate();

        if ($this->Key) {
            $sameKeyMenuSets = self::get()
                ->filter('Key', $this->Key)
                ->exclude('ID', $this->ID);
            if ($sameKeyMenuSets->count() > 0) {
                $result->addFieldError('Key', 'You must use a unique key');
            }
        }
        else {
            $result->addFieldError('Key', 'You must provide a key');
        }

        if (!$this->Name) {
            $result->addFieldError('Name', 'You must provide a name');
        }

        return $result;
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
        $this->extend('updateMenuItemClasses', $list);
        return $list;
    }

    public function CMSEditLink()
    {
        $siteConfig = SiteConfig::current_site_config();
        $link = Controller::join_links(
            $siteConfig->CMSEditLink(),
            'EditForm/field/MenuSets/item',
            $this->ID
        );
        $this->extend('updateCMSEditLink', $link);
        return $link;
    }

    public function getPermissionKey()
    {
        return 'MENUS_EDIT_' . $this->obj('Key')->Uppercase();
    }

    public function providePermissions()
    {
        $permissions = [];
        foreach (self::get() as $menuSet) {
            $key = $menuSet->getPermissionKey();
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
        return Permission::check($this->getPermissionKey(), 'any', $member);
    }

    public function canView($member = null)
    {
        return Permission::check($this->getPermissionKey(), 'any', $member);
    }
}
