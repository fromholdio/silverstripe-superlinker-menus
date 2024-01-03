<?php

namespace Fromholdio\SuperLinkerMenus\Tasks;

use SilverStripe\Control\Director;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;

class SuperLinkerMenusUpgradeTask extends BuildTask
{

    protected $enabled = true;

    protected $title = 'SuperLinker Menus Upgrade';

    protected $description = 'Upgrade SuperLinker Menus to version 5';

    private static $segment = 'superlinker-menus-upgrade';

    public function run($request)
    {
        $this->log("Starting upgrade...");

        set_time_limit(0);

        $this->upgradeEmailLinks();
        $this->upgradeExternalLinks();
        $this->upgradeFileLinks();
        $this->upgradeGlobalAnchorLinks();
        $this->upgradePhoneLinks();
        $this->upgradeSiteTreeLinks();
        $this->upgradeSystemLinks();

        $this->cleanupTables();

        $this->log("Upgrade done.");
    }

    private function upgradeFileLinks()
    {
        $this->log("upgrade file links... ", false);
        $query = "SHOW TABLES LIKE 'MenuItemFile'";
        $tableExists = DB::query($query)->value();
        if ($tableExists != null) {
            $query = <<<EOT
update SuperLink s
LEFT JOIN MenuItemFile f ON f.ID = s.ID
set
s.ClassName = 'Fromholdio\\\\SuperLinkerMenus\\\\Model\\\\MenuItem',
s.LinkType = 'file',
s.FileID = f.FileID,
s.LinkText = s.CustomLinkText,
s.DoOpenInNew = s.DoOpenNewWindow
where s.ClassName = 'Fromholdio\\\\SuperLinkerMenus\\\\Model\\\\MenuItemFile';
EOT;
            DB::query($query);
            $query = "DROP TABLE IF EXISTS MenuItemFile";
            DB::query($query);
            $query = "DROP TABLE IF EXISTS MenuItemFile_Live";
            DB::query($query);
            $query = "DROP TABLE IF EXISTS MenuItemFile_Versions";
            DB::query($query);
        }

        $this->log("done.");
    }

    private function upgradeSiteTreeLinks()
    {
        $this->log("upgrade page links... ", false);
        $query = "SHOW TABLES LIKE 'MenuItemSiteTree'";
        $tableExists = DB::query($query)->value();
        if ($tableExists != null) {
            $query = <<<EOT
update SuperLink s
LEFT JOIN MenuItemSiteTree p ON p.ID = s.ID
set
s.ClassName = 'Fromholdio\\\\SuperLinkerMenus\\\\Model\\\\MenuItem',
s.LinkType = 'sitetree',
s.SiteTreeID = p.SiteTreeID,
s.LinkText = s.CustomLinkText,
s.DoOpenInNew = s.DoOpenNewWindow
where s.ClassName = 'Fromholdio\\\\SuperLinkerMenus\\\\Model\\\\MenuItemSiteTree'
EOT;
            DB::query($query);
            $query = "DROP TABLE IF EXISTS MenuItemSiteTree";
            DB::query($query);
            $query = "DROP TABLE IF EXISTS MenuItemSiteTree_Live";
            DB::query($query);
            $query = "DROP TABLE IF EXISTS MenuItemSiteTree_Versions";
            DB::query($query);
        }

        $this->log("done.");
    }

    private function upgradeExternalLinks()
    {
        $this->log("upgrade external links... ", false);
        $query = <<<EOT
update SuperLink
set
ClassName = 'Fromholdio\\\\SuperLinkerMenus\\\\Model\\\\MenuItem',
LinkType = 'external',
ExternalURL = concat(URL,CASE WHEN QueryString IS not NULL then concat('?',QueryString) else '' end,CASE WHEN Anchor IS not NULL then concat('#',Anchor) else '' end),
LinkText = CustomLinkText,
DoOpenInNew = DoOpenNewWindow
where ClassName = 'Fromholdio\\\\SuperLinkerMenus\\\\Model\\\\MenuItemExternalURL'
EOT;
        DB::query($query);

        $this->log("done.");
    }

    private function upgradeEmailLinks()
    {
        $this->log("upgrade email links... ", false);
        $query = "SHOW TABLES LIKE 'MenuItemEmail'";
        $tableExists = DB::query($query)->value();
        if ($tableExists != null) {
            $query = <<<EOT
update SuperLink s
LEFT JOIN MenuItemEmail e ON e.ID = s.ID
set
s.ClassName = 'Fromholdio\\\\SuperLinkerMenus\\\\Model\\\\MenuItem',
s.LinkType = 'email',
s.Email = e.Email,
s.EmailCC = e.EmailCC,
s.EmailBCC = e.EmailBCC,
s.EmailSubject = e.Subject,
s.EmailBody = e.Body,
s.LinkText = s.CustomLinkText,
s.DoOpenInNew = s.DoOpenNewWindow
where s.ClassName = 'Fromholdio\\\\SuperLinkerMenus\\\\Model\\\\MenuItemEmail'
EOT;
            DB::query($query);
            $query = "DROP TABLE IF EXISTS MenuItemEmail";
            DB::query($query);
            $query = "DROP TABLE IF EXISTS MenuItemEmail_Live";
            DB::query($query);
            $query = "DROP TABLE IF EXISTS MenuItemEmail_Versions";
            DB::query($query);
        }

        $this->log("done.");
    }

    private function upgradePhoneLinks()
    {
        $this->log("upgrade phone links... ", false);
        $query = "SHOW TABLES LIKE 'MenuItemPhone'";
        $tableExists = DB::query($query)->value();
        if ($tableExists != null) {
            $query = <<<EOT
update SuperLink s
LEFT JOIN MenuItemPhone p ON p.ID = s.ID
set
s.ClassName = 'Fromholdio\\\\SuperLinkerMenus\\\\Model\\\\MenuItem',
s.LinkType = 'phone',
s.PhoneNumber = p.Phone,
s.LinkText = s.CustomLinkText,
s.DoOpenInNew = s.DoOpenNewWindow
where s.ClassName = 'Fromholdio\\\\SuperLinkerMenus\\\\Model\\\\MenuItemPhone'
EOT;
            DB::query($query);
            $query = "DROP TABLE IF EXISTS MenuItemPhone";
            DB::query($query);
            $query = "DROP TABLE IF EXISTS MenuItemPhone_Live";
            DB::query($query);
            $query = "DROP TABLE IF EXISTS MenuItemPhone_Versions";
            DB::query($query);
        }

        $this->log("done.");
    }

    private function upgradeGlobalAnchorLinks()
    {
        $this->log("upgrade global anchor links... ", false);
        $query = <<<EOT
update SuperLink
set
ClassName = 'Fromholdio\\\\SuperLinkerMenus\\\\Model\\\\MenuItem',
LinkType = 'globalanchor',
GlobalAnchorKey = Anchor,
LinkText = CustomLinkText,
DoOpenInNew = DoOpenNewWindow
where ClassName = 'Fromholdio\\\\SuperLinkerMenus\\\\Model\\\\MenuItemGlobalAnchor'
EOT;
        DB::query($query);

        $this->log("done.");
    }

    private function upgradeSystemLinks()
    {
        $this->log("upgrade system links... ", false);
        $query = "SHOW TABLES LIKE 'MenuItemSystemLink'";
        $tableExists = DB::query($query)->value();
        if ($tableExists != null) {
            $query = <<<EOT
update SuperLink s
LEFT JOIN MenuItemSystemLink p ON p.ID = s.ID
set
s.ClassName = 'Fromholdio\\\\SuperLinkerMenus\\\\Model\\\\MenuItem',
s.LinkType = 'system',
s.SystemLinkKey = p.Key,
s.LinkText = s.CustomLinkText,
s.DoOpenInNew = s.DoOpenNewWindow
where s.ClassName = 'Fromholdio\\\\SuperLinkerMenus\\\\Model\\\\MenuItemSystemLink'
EOT;
            DB::query($query);
            $query = "DROP TABLE IF EXISTS MenuItemSystemLink";
            DB::query($query);
            $query = "DROP TABLE IF EXISTS MenuItemSystemLink_Live";
            DB::query($query);
            $query = "DROP TABLE IF EXISTS MenuItemSystemLink_Versions";
            DB::query($query);
        }

        $this->log("done.");
    }

    private function cleanupTables()
    {
        $this->log("clean up tables... ", false);

        $query = "DROP TABLE IF EXISTS MenuItem_Live";
        DB::query($query);
        $query = "DROP TABLE IF EXISTS MenuItem_Versions";
        DB::query($query);

        $this->log("done.");
    }

    public function log($message, $newLine = true)
    {
        if (Director::is_cli()) {
            echo "{$message}" . ($newLine ? "\n" : "");
        } else {
            echo "{$message}" . ($newLine ? "<br />" : "");
        }
        flush();
    }
}
