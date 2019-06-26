<?php

namespace Fromholdio\SuperLinkerMenus\Model;

use Fromholdio\GlobalAnchors\GlobalAnchors;
use Fromholdio\SuperLinker\Extensions\SiteTreeLink;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldGroup;
use UncleCheese\DisplayLogic\Forms\Wrapper;

class MenuItemSiteTree extends MenuItem
{
    private static $table_name = 'MenuItemSiteTree';

    private static $extensions = [
        SiteTreeLink::class,
        GlobalAnchors::class
    ];

    private static $db = [
        'DoUseSiteTreeChildren' => 'Boolean'
    ];

    private static $field_labels = [
        'DoUseSiteTreeChildren' => 'Build submenu from children of selected target page',
        'SiteTree' => 'Target Page'
    ];

    public function getGlobalAnchors()
    {
        return GlobalAnchors::get_anchors();
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        if ($this->isAllowedChildren()) {

            $useTreeChildren = FieldGroup::create(
                'Submenu Items',
                CheckboxField::create(
                    'DoUseSiteTreeChildren',
                    $this->fieldLabel('DoUseSiteTreeChildren')
                )
            );
            $fields->replaceField('DoUseSiteTreeChildren', $useTreeChildren);

            $childrenField = $fields->dataFieldByName('Children');
            $childrenWrapper = Wrapper::create($childrenField);
            $fields->replaceField('Children', $childrenWrapper);

            $childrenWrapper->displayIf('DoUseSiteTreeChildren')->isNotChecked();
        }
        else {
            $fields->removeByName('DoUseSiteTreeChildren');
        }

        return $fields;
    }
}
