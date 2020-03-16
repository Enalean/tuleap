<?php
/**
 * Copyright (c) Enalean, 2014-2018. All Rights Reserved.
 * Copyright 2005, STMicroelectronics
 *
 * Originally written by Manuel Vacelet
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

define('PHPWIKI_DIR', dirname(__FILE__) . '/../phpwiki');
require_once PHPWIKI_DIR . '/lib/prepend.php';
require_once PHPWIKI_DIR . '/lib/IniConfig.php';

/**
 * Wrapper to access to PhpWiki WikiPage objects
 *
 * This class wrap WikiDB object located in phpwiki/lib/WikiDB.php
 *
 */
class WikiPageWrapper
{
  /* private string */ public $wikiname;
  /* private int    */ public $gid;


    public function __construct($id = 0)
    {
        $this->gid = (int) $id;

        $pm = ProjectManager::instance();
        $go = $pm->getProject($this->gid);

        $this->wikiname = ucfirst($go->getUnixName()) . 'Wiki';

      // Set PhpWiki init values
        define('WIKI_NAME', $this->wikiname);
        define('GROUP_ID', $this->gid);
        define('PLUGIN_CACHED_CACHE_DIR', $GLOBALS['codendi_cache_dir']);
    }

  /**
   * @return WikiRequest
   */
    public function getRequest()
    {
        define('PHPWIKI_NOMAIN', true);
        IniConfig(PHPWIKI_DIR . "/config/config.ini");
        ini_set('include_path', PHPWIKI_DIR . ':' . ini_get('include_path'));

        require_once(PHPWIKI_DIR . '/lib/WikiDB.php');
        require_once(PHPWIKI_DIR . '/lib/main.php');

        return new WikiRequest();
    }

    public function getProjectEmptyLinks()
    {
      // Dirty hack to 'give' a WikiRequest object to phpwiki
      // Obscure functions seems require it.
        $request = $this->getRequest();

        $page = 'ProjectWantedPages';

        $dbi = $request->getDbh();
        $pagehandle = $dbi->getPage($page);

        $links = $pagehandle->getPageLinks(true);
        $allPages = array();
        while ($link_handle = $links->next()) {
            if (!$dbi->isWikiPage($linkname = $link_handle->getName())) {
                $allPages[] = $link_handle->getName();
            }
        }
        return $allPages;
    }

    public function addNewProjectPage($pagename)
    {
        $projectPageName = 'ProjectWantedPages';

      // Dirty hack to 'give' a WikiRequest object to phpwiki
      // So obscure functions seems require it.
        $request = $this->getRequest();

        $dbi = $request->getDbh();
        require_once(PHPWIKI_DIR . "/lib/loadsave.php");
        $pagehandle = $dbi->getPage($projectPageName);
        if ($pagehandle->exists()) {// don't replace default contents
            $current = $pagehandle->getCurrentRevision();
            $version = $current->getVersion();
            $text = $current->getPackedContent();
            $meta = $current->_data;
        } else {
          // Create a new page (first use or page previously erased)
            $version = 0;
            $text = $GLOBALS['Language']->getText(
                'wiki_lib_wikipagewrap',
                'new_page_text',
                array($projectPageName)
            );
        }

        $page_created = $dbi->getPage($pagename);
        if ($this->wikiPageDoesNotExistInCreatedAndEmptyPages($page_created, $pagename)) {
            $event_manager = EventManager::instance();
            $user_manager  = UserManager::instance();
            $user          = $user_manager->getCurrentUser();
            $event_manager->processEvent(
                "wiki_page_created",
                array(
                'group_id'         => $this->gid,
                'wiki_page'        => $pagename,
                'user'             => $user,
                'version'          => $version
                )
            );
        }

        $text .= "\n* [$pagename]";
        $meta['summary'] =  $GLOBALS['Language']->getText(
            'wiki_lib_wikipagewrap',
            'page_added',
            array($pagename)
        );
        $meta['author'] = user_getname();
        $pagehandle->save($text, $version + 1, $meta);
    }

    private function wikiPageDoesNotExistInCreatedAndEmptyPages($page_created, $pagename)
    {
        return ! $page_created->exists() && ! in_array($pagename, $this->getProjectEmptyLinks());
    }

    public function addUploadPage()
    {
        // Dirty hack to 'give' a WikiRequest object to phpwiki
        // So obscure functions seems require it.
        $request = $this->getRequest();

        $dbi = $request->getDbh();
        require_once(PHPWIKI_DIR . "/lib/loadsave.php");
        $pagehandle = $dbi->getPage("UpLoad");
        if ($pagehandle->exists()) {// don't replace default contents
            $current = $pagehandle->getCurrentRevision();
            $version = $current->getVersion();
            $text = $current->getPackedContent();
            $meta = $current->_data;
        } else {
            // Create a new page (first use or page previously erased)
            $version = 0;
            $text = '__Upload a file which will be accessible by typing:__
<verbatim>
Upload:num_rev/filename
</verbatim>

----
<?plugin UpLoad ?>
----
';
            $meta['author'] = user_getname();
        }

        $meta['summary'] = "Page created";
        $pagehandle->save($text, $version + 1, $meta);
    }


    public function render($lite = false, $full_screen = false)
    {
        if ($lite) {
            define('THEME', 'Codendi-lite');
        }
        if ($full_screen) {
            define('THEME', 'Codendi-light-printer-version');
        }

        IniConfig(PHPWIKI_DIR . "/config/config.ini");
        ini_set('include_path', PHPWIKI_DIR . ':' . ini_get('include_path'));
        include(PHPWIKI_DIR . "/codendi.php");
    }

  /**
   * special install function
   *
   */
    public function install()
    {
        if ($this->gid == 1) {
            if (!user_is_super_user()) {
                exit_error(
                    $GLOBALS['Language']->getText('global', 'error'),
                    $GLOBALS['Language']->getText(
                        'wiki_lib_wikipagewrap',
                        'right_error'
                    )
                );
            }
        }
        $we = new WikiEntry();
        $we->setGid($this->gid);
        $we->setLanguage_id($_REQUEST['language_id']);

        $name_fr = "Page d'accueil";
        $page_fr = "PageAccueil";
        $desc_fr = "Document initial du Wiki";
        $name_en = "Home Page";
        $page_en = "HomePage";
        $desc_en = "Initial wiki document";
        switch ($we->getLanguage_id()) {
              // English
            case 'en_US':
                define('WIKI_PGSRC', 'codendipgsrc');
                define('DEFAULT_WIKI_PGSRC', PHPWIKI_DIR . '/codendipgsrc');
                $we->setName($name_en);
                $we->setPage($page_en);
                $we->setDesc($desc_en);
                break;
         // French
            case 'fr_FR':
                define('WIKI_PGSRC', 'pgsrc');
                define('DEFAULT_WIKI_PGSRC', PHPWIKI_DIR . '/locale/fr/pgsrc');
                $we->setName($name_fr);
                $we->setPage($page_fr);
                $we->setDesc($desc_fr);
                break;

            default:
                define('WIKI_PGSRC', 'codendipgsrc');
                define('DEFAULT_WIKI_PGSRC', PHPWIKI_DIR . '/codendipgsrc');
                $we->setName($name_en);
                $we->setPage($page_en);
                $we->setDesc($desc_en);
        }
        $we->add();
        $this->render();
    }

    public function getNextGroupWithWiki($currentGroupId, &$nbMatchFound)
    {
        $nextId = null;

        $sql = sprintf(
            'SELECT SQL_CALC_FOUND_ROWS DISTINCT group_id' .
                       ' FROM wiki_page' .
                       ' WHERE group_id > %d' .
                       ' ORDER BY group_id ASC' .
                       ' LIMIT 1',
            $currentGroupId
        );
          $res = db_query($sql);
        if ($res) {
            if ($row = db_fetch_array($res)) {
                $nextId = $row['group_id'];

                $sql          = 'SELECT FOUND_ROWS() AS nb';
                $res          = db_query($sql);
                $row          = db_fetch_array($res);
                $nbMatchFound = $row['nb'];
            }
        }

          return $nextId;
    }

    public function upgrade()
    {
        global $request;
        global $WikiTheme;

        define('WIKI_PGSRC', 'codendipgsrc');
        define('DEFAULT_WIKI_PGSRC', PHPWIKI_DIR . '/codendipgsrc');
        define('ENABLE_EMAIL_NOTIFIFICATION', false);

        $request = $this->getRequest();
        $request->setArg('overwrite', 'true');

        require_once(PHPWIKI_DIR . "/lib/upgrade.php");
        // WikiTheme and those files are required because of the WikiLink
        // function used during upgrade process.
        require_once(PHPWIKI_DIR . "/lib/Theme.php");
        require_once(PHPWIKI_DIR . "/themes/Codendi/themeinfo.php");

        $check = false;
        CheckActionPageUpdate($request, $check);
        CheckPgsrcUpdate($request, $check);
    }
}
