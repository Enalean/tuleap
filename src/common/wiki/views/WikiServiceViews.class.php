<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

use Tuleap\PHPWiki\WikiPage;

require_once(dirname(__FILE__) . '/WikiViews.class.php');
require_once(dirname(__FILE__) . '/../lib/WikiPage.class.php');
require_once(dirname(__FILE__) . '/../lib/WikiEntry.class.php');

class WikiServiceViews extends WikiViews
{
    protected $purifier;
    private $base_url;

  /**
   * WikiServiceViews - Constructor
   */
    public function __construct(&$controler, $id = 0, $view = null)
    {
        $this->purifier = Codendi_HTMLPurifier::instance();
        parent::WikiView($controler, $id, $view);
        $pm = ProjectManager::instance();
        if (isset($_REQUEST['pagename']) && ! is_null($_REQUEST['pagename'])) {
            $this->title    = $GLOBALS['Language']->getText(
                'wiki_views_wikiserviceviews',
                'wiki_page_title',
                [ $this->purifier->purify($_REQUEST['pagename'], CODENDI_PURIFIER_CONVERT_HTML) ,
                    $pm->getProject($this->gid)->getPublicName(),
                ]
            );
            $this->base_url = '/wiki/index.php?group_id=' . $this->gid . '&pagename=' . urlencode($_REQUEST['pagename']);
        } else {
            $this->title    = $GLOBALS['Language']->getText(
                'wiki_views_wikiserviceviews',
                'wiki_title',
                [$pm->getProject($this->gid)->getPublicName()]
            );
            $this->base_url = '/wiki/index.php?group_id=' . $this->gid;
        }
        $GLOBALS['wiki_view'] = $this;
    }

  /**
   * View
   *
   * <p>Default browsing page of Wiki Service.
   * It display</p>
   * <ul>
   * <li>Wiki Documents - _browseWikiDocuments</li>
   * <li>Project Wiki Pages _browseProjectWikiPage </li>
   * <li>Empty Wiki Pages - _browseEmptyWikiPage</li>
   * <li>A form to create new pages - _newPageForm</li>
   * </ul>
   * @access public
   */
    public function browse()
    {
        list($hideFlag, $hideUrl, $hideImg) = hide_url('wiki_browse_documents', $this->gid);
        $hurl                               = '<a href="' . $this->wikiLink . '&' . $hideUrl . '">' . $hideImg . '</a>';
        print $GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'wiki_subtit_docu', [$hurl]);
        if (! $hideFlag) {
            $this->_browseWikiDocuments();
        }
    }

  /**
   *  View
   *
   * <p>Page browsing page of Wiki Service.
   * It display</p>
   * <ul>
   * <li>Project Wiki Pages _browseProjectWikiPage </li>
   * <li>Empty Wiki Pages - _browseEmptyWikiPage</li>
   * <li>A form to create new pages - _newPageForm</li>
   * </ul>
   * @access public
   */
    public function browsePages()
    {
        list($hideFlag, $hideUrl, $hideImg) = hide_url('wiki_browse_pages', $this->gid);
        $hurl                               = '<a href="' . $this->wikiLink . '&view=browsePages&' . $hideUrl . '">' . $hideImg . '</a>';
        print $GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'wiki_subtit_pages', [$hurl]);
        if (! $hideFlag) {
            $this->_browseProjectWikiPages();
        }

        list($hideFlag, $hideUrl, $hideImg) = hide_url('wiki_browse_empty_pages', $this->gid);
        $hurl                               = '<a href="' . $this->wikiLink . '&view=browsePages&' . $hideUrl . '">' . $hideImg . '</a>';
        print $GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'wiki_subtit_empty', [$hurl]);
        if (! $hideFlag) {
            $this->_browseEmptyWikiPages();
        }

        list($hideFlag, $hideUrl, $hideImg) = hide_url('wiki_create_new_page', $this->gid);
        $hurl                               = '<a href="' . $this->wikiLink . '&view=browsePages&' . $hideUrl . '">' . $hideImg . '</a>';
        print $GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'wiki_subtit_create', [$hurl]);
        if (! $hideFlag) {
            $this->_newPageForm($this->wikiLink . '&view=browsePages');
        }
    }

    public function _browseWikiDocuments()
    {
        $wei = WikiEntry::getEntryIterator($this->gid);

        print '<ul class="WikiEntries">';
        while ($wei->valid()) {
            $we = $wei->current();

            $href = $this->_buildPageLink($we->wikiPage, $we->getName());
            if (! empty($href)) {
                $description = $this->purifier->purify($we->getDesc());
                print $GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'wikientries', [$href, $description]);
            }

            $wei->next();
        }
        print '</ul>';
    }

  /**
   * _browseProjectPages - private
   *
   * Display project pages.
   */
    public function _browseProjectWikiPages()
    {
        WikiPage::globallySetProjectID($this->gid);
        $allPages = WikiPage::getAllUserPages();
        $this->_browsePages($allPages);
    }

  /**
   * _browseProjectPages - private
   *
   * Display empty pages.
   */
    public function _browseEmptyWikiPages()
    {
        $wpw      = new WikiPageWrapper($this->gid);
        $allPages = $wpw->getProjectEmptyLinks();
        $this->_browsePages($allPages);
    }

  /**
   * _browsePages - private
   *
   * @param  string[] &$pageList List of page names.
   */
    public function _browsePages(&$pageList)
    {
        print '<ul class="WikiEntries">';
        foreach ($pageList as $pagename) {
            $wp   = new WikiPage($this->gid, $pagename);
            $href = $this->_buildPageLink($wp);
            if (! empty($href)) {
                print '<li>' . $href . '</li>';
            }
        }
        print "</ul>";
    }

  /**
   * _newPageForm - private
   *
   * @param  string $addr Form action adress
   */
    public function _newPageForm($addr = '')
    {
        print '
    <form name="newPage" method="post" action="' . $addr . '">
      <input type="hidden" name="action" value="add_temp_page" />
      <input type="hidden" name="group_id" value="' . $this->gid . '" />' . $GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'pagename') . ' <input type="text" name="name" value="" size="20" maxsize="255" data-test="new-wiki-page" />
      <input type="submit" value="' . $GLOBALS['Language']->getText('global', 'btn_create') . '">
    </form>';
    }

  /**
   * _buildPageLink - private
   *
   * @param  WikiPage $wikiPage
   * @param  string   $title
   * @return string   $href
   */
    public function _buildPageLink(&$wikiPage, $title = null)
    {
        $href = '';
      // Check permission
        if ($wikiPage->isAutorized(UserManager::instance()->getCurrentUser()->getId())) {
            $pagename = $wikiPage->getPagename();

          // Build page link
            if (empty($title)) {
                $title = $pagename;
            }

            $title = $this->purifier->purify($title, CODENDI_PURIFIER_CONVERT_HTML);

            $link = '/wiki/index.php?group_id=' . $this->gid . '&pagename=' . urlencode($pagename);

          // Display title as emphasis if corresponding page does't exist.
            if ($wikiPage->isEmpty()) {
                $title = '<em>' . $title . '</em>';
                $link .= '&action=edit';
            }

          // Build Lock image if a permission is set on the corresponding page
            if ($wikiPage->permissionExist()) {
                $permLink = $this->wikiLink . '&view=pagePerms&id=' . $wikiPage->getId();
                $title    = $title . '<img src="' . util_get_image_theme("ic/lock.png") . '" border="0" alt="Lock" />';
            }

            $href = '<a href="' . $link . '" data-test="phpwiki-page-' . urlencode($pagename) . '">' . $title . '</a>';
        }
        return $href;
    }

    protected function addStylesheets(): void
    {
        $GLOBALS['Response']->addStylesheet('/wiki/themes/Codendi/phpwiki.css');
        parent::addStylesheets();
    }

  /**
   * displayMenu - public
   */
    public function displayMenu()
    {
        print '
    <table class="ServiceMenu">
      <tr>
        <td>';
        $language_id = '';
        if (defined('DEFAULT_LANGUAGE')) {
            $language_id = DEFAULT_LANGUAGE;
        }
        switch ($language_id) {
            case 'fr_FR':
                 $attatch_page    = "DéposerUnFichier";
                $preferences_page = "PréférencesUtilisateurs";
                break;
            case 'en_US':
            default:
                $attatch_page     = 'UpLoad';
                $preferences_page = 'UserPreferences';
                break;
        }
        $attatch_menu     = $GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'menuattch');
        $preferences_menu = $GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'menuprefs');
        $help_menu        = $GLOBALS['Language']->getText('global', 'help');
        print '
    <ul class="ServiceMenu">
      <li><a href="' . $this->wikiLink . '&view=browsePages" data-test="wiki-browse-pages">' . $GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'menupages') . '</a>&nbsp;|&nbsp;</li>';
        if (UserManager::instance()->getCurrentUserWithLoggedInInformation()->is_logged_in) {
            print '<li><a data-help-window href="' . $this->wikiLink . '&pagename=' . $attatch_page . '&pv=1">' . $attatch_menu . '</a>&nbsp;|&nbsp;</li>';
            print '<li><a href="' . $this->wikiLink . '&pagename=' . $preferences_page . '" data-test="wiki-preferences">' . $preferences_menu . '</a>&nbsp;|&nbsp;</li>';
        }
        if (user_ismember($this->gid, 'W2') || user_ismember($this->gid, 'A')) {
            print '<li><a href="' . $this->wikiAdminLink . '" data-test="wiki-admin">' . $GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'menuadmin') . '</a>&nbsp;|&nbsp;</li>';
        }

        print '</ul>';

        print '
  </td>
  <td align="right" valign="top">';

        if (user_ismember($this->gid, 'W2') || user_ismember($this->gid, 'A')) {
              $wiki     = new Wiki($this->gid);
              $permInfo = "";
            if ('wiki' == $this->view) {
            // User is browsing a wiki page
                $wp = new WikiPage($this->gid, $_REQUEST['pagename']);

                $permLink = Codendi_HTMLPurifier::instance()->purify($this->wikiAdminLink . '&view=pagePerms&id=' . urlencode((string) $wp->getId()));
                if ($wp->permissionExist()) {
                      $permInfo =  '<a href="' . $permLink . '"> ' . '<img src="' . util_get_image_theme("ic/lock.png") . '" border="0" alt="' . $GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'lock_alt') . '" title="' . $GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'lock_title_spec') . '"/></a>';
                }
            }
            if ($wiki->permissionExist()) {
                $permInfo .=  '<a href="/wiki/admin/index.php?group_id=' . $this->gid . '&view=wikiPerms"> ' . '<img src="' . util_get_image_theme("ic/lock.png") . '" border="0" alt="' . $GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'lock_alt') . '" title="' . $GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'lock_title_set') . '"/>' . '</a>';
            }
            if ($permInfo) {
                print $permInfo;
            }
        }

    //Display printer_version link only in wiki pages
        if (isset($_REQUEST['pagename'])) {
              print '
          (<a href="' . $this->base_url . '&pv=1" title="' . $GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'lighter_display') . '">
          <img src="' . util_get_image_theme("msg.png") . '" border="0">&nbsp;' .
              $GLOBALS['Language']->getText('global', 'printer_version') . '</A> )
          </li>';
        }

        print '
     </td>
    </tr>
  </table>';
    }

  /**
   * View "Wiki Page access rights permissions"
   *
   * Page for Wiki Page permissions modifications.
   *
   * @access public
   */
    public function pagePerms()
    {
        $postUrl = '/wiki/index.php?group_id=' . $this->gid . '&action=setWikiPagePerms';
        $this->_pagePerms($postUrl);
        print '<p><a href="' . $this->wikiLink . '">' . $GLOBALS['Language']->getText('global', 'back') . '</a></p>' . "\n";
    }

  /**
   * View display a Wiki Page.
   *
   * @access public
   */
    public function wiki()
    {
        $wp = new WikiPage($this->gid, $_REQUEST['pagename']);

        $wp->log(UserManager::instance()->getCurrentUser()->getId());

        $lite        = false;
        $full_screen = false;
        if (isset($_GET['pv']) && ( $_GET['pv'] == 1)) {
            $lite = true;
        }
        if (isset($_GET['pv']) && ( $_GET['pv'] == 2)) {
            $full_screen = true;
        }
        $wp->render($lite, $full_screen);
    }

  /**
   * display - public
   * @access public
   */
    public function display($view = '')
    {
        $GLOBALS['type_of_search'] = 'wiki';

        switch ($view) {
            case 'empty':
                $this->wiki();
                break;

            case 'doinstall':
                if (! empty($view)) {
                    $this->$view();
                }
                break;

            case 'browse':
            default:
                $this->header();
                if (! empty($view)) {
                    $this->$view();
                }
                $this->footer();
        }
    }

  /**
   * install: ask for confirmation and choose language
   */
    public function install()
    {
        echo $GLOBALS['Language']->getText(
            'wiki_views_wikiserviceviews',
            'install_intro',
            [$GLOBALS['Language']->getText('global', 'btn_create')]
        );
      // Display creation form
        echo '<form name="WikiCreation" method="post" action="' . $this->wikiLink . '">
             <input type="hidden" name="group_id" value="' . $this->gid . '" />
             <input type="hidden" name="view" value="doinstall" />' . $GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'wiki_language') . ' ';
             echo html_get_language_popup($GLOBALS['Language'], 'language_id', UserManager::instance()->getCurrentUser()->getLocale());
        echo '<input type="submit" value="' . $GLOBALS['Language']->getText('global', 'btn_create') . '" data-test="create-wiki">
</form>';
    }

  /**
   * install
   */
    public function doinstall()
    {
        global $LANG;
        global $language_id;
        $language_id = $_REQUEST['language_id'];
        if (! $language_id || ! $GLOBALS['Language']->isLanguageSupported($language_id)) {
            $language_id = $GLOBALS['Language']->defaultLanguage;
        }
        // Initial Wiki document is now created within phpWiki main()
        // Make sure phpWiki instantiates the right pages corresponding the the given language
        define('DEFAULT_LANGUAGE', $language_id);
        $LANG = $language_id;
        $wpw  = new WikiPageWrapper($this->gid);
        $wpw->install();
    }
}
