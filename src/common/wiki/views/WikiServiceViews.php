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

use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\Layout\CssViteAsset;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\PHPWiki\WikiPage;

require_once(dirname(__FILE__) . '/WikiViews.php');
require_once(dirname(__FILE__) . '/../lib/WikiPage.php');
require_once(dirname(__FILE__) . '/../lib/WikiEntry.php');

class WikiServiceViews extends WikiViews // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    protected $purifier;
    private $base_url;

  /**
   * WikiServiceViews - Constructor
   */
    public function __construct(&$controler, $id = 0, $view = null)
    {
        $this->purifier = Codendi_HTMLPurifier::instance();
        parent::__construct($controler, $id, $view);
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

    public function browse()
    {
        $this->browseWikiDocuments();
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
    public function browsePages(): void
    {
        echo '
        <section class="tlp-pane">
            <div class="tlp-pane-container">
                <div class="tlp-pane-header">
                    <h1 class="tlp-pane-title">' . $GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'wiki_subtit_pages') . '</h1>
                </div>
                <section class="tlp-pane-section">
        ';
        $this->browseProjectWikiPages();
        echo <<<EOT
                </section>
            </div>
        </section>
        EOT;

        echo '
        <section class="tlp-pane">
            <div class="tlp-pane-container">
                <div class="tlp-pane-header">
                    <h1 class="tlp-pane-title">' . $GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'wiki_subtit_empty') . '</h1>
                </div>
                <section class="tlp-pane-section">
        ';
        $this->browseEmptyWikiPages();
        echo <<<EOT
                </section>
            </div>
        </section>
        EOT;

        echo '
        <section class="tlp-pane">
            <div class="tlp-pane-container">
                <div class="tlp-pane-header">
                    <h1 class="tlp-pane-title">' . $GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'wiki_subtit_create') . '</h1>
                </div>
                <section class="tlp-pane-section">
        ';
        $this->newPageForm($this->wikiLink . '&view=browsePages');
        echo <<<EOT
                </section>
            </div>
        </section>
        EOT;
    }

    private function browseWikiDocuments(): void
    {
        $wei = WikiEntry::getEntryIterator($this->gid);

        print '<div class="tlp-card">';
        print '<ul class="WikiEntries">';
        while ($wei->valid()) {
            $we = $wei->current();

            $href = $this->buildPageLink($we->wikiPage, $we->getName());
            if (! empty($href)) {
                $description = $this->purifier->purify($we->getDesc());
                print $GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'wikientries', [$href, $description]);
            }

            $wei->next();
        }
        print '</ul>';
        print '</div>';
    }

    private function browseProjectWikiPages(): void
    {
        WikiPage::globallySetProjectID($this->gid);
        $allPages = WikiPage::getAllUserPages();
        $this->browsePageList($allPages);
    }

    private function browseEmptyWikiPages(): void
    {
        $wpw      = new WikiPageWrapper($this->gid);
        $allPages = $wpw->getProjectEmptyLinks();
        $this->browsePageList($allPages);
    }

    private function browsePageList(array $pageList): void
    {
        print '<ul class="WikiEntries">';
        foreach ($pageList as $pagename) {
            $wp   = new WikiPage($this->gid, $pagename);
            $href = $this->buildPageLink($wp);
            if (! empty($href)) {
                print '<li>' . $href . '</li>';
            }
        }
        print '</ul>';
    }

    private function newPageForm(string $addr = ''): void
    {
        print '
    <form name="newPage" method="post" action="' . $addr . '">
      <input type="hidden" name="action" value="add_temp_page" />
      <input type="hidden" name="group_id" value="' . $this->gid . '" />
      <div class="tlp-form-element">
        <label class="tlp-label" for="name">' . $GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'pagename') . '</label>
        <input type="text" name="name" id="name" value="" size="20" maxsize="255" data-test="new-wiki-page" class="tlp-input" />
      </div>
      <div class="tlp-pane-section-submit">
        <input type="submit" class="tlp-button-primary" value="' . $GLOBALS['Language']->getText('global', 'btn_create') . '">
      </div>
    </form>';
    }

    private function buildPageLink(WikiPage $wikiPage, ?string $title = null): string
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

            if ($wikiPage->isEmpty()) {
                $title = '<em>' . $title . '</em>';
                $link .= '&action=edit';
            }

            $lock = '';
            if ($wikiPage->permissionExist()) {
                $lock = ' <i class="fa-solid fa-lock" aria-hidden="true"></i>';
            }

            $href = '<a href="' . $link . '" data-test="phpwiki-page-' . urlencode($pagename) . '">' . $title . '</a>' . $lock;
        }
        return $href;
    }

    #[\Override]
    public function header(): void
    {
        $breadcrumbs = new BreadCrumbCollection();
        $breadcrumbs->addBreadCrumb($this->getServiceCrumb());
        $GLOBALS['HTML']->addBreadcrumbs($breadcrumbs);

        $GLOBALS['Response']->addCssAsset(CssViteAsset::fromFileName(
            new IncludeViteAssets(
                __DIR__ . '/../../../scripts/phpwiki/frontend-assets',
                '/assets/core/phpwiki',
            ),
            'src/phpwiki.scss',
        ));

        $project = ProjectManager::instance()->getProject($this->gid);
        site_project_header(
            $project,
            \Tuleap\Layout\HeaderConfigurationBuilder::get($this->title)
                ->inProject($project, Service::WIKI)
                ->build()
        );
        echo '<h1 class="project-administration-title">PhpWiki</h1>';
        $this->displayMenu();
        echo '<div class="tlp-framed phpwiki-service-content">';
        if (! ForgeConfig::getFeatureFlag(self::FEATURE_FLAG_REMOVE_DEPRECATION_MESSAGE)) {
            echo '<div class="tlp-alert-warning">' . _('PhpWiki is deprecated and will be removed in Spring 2026. Please use Mediawiki Standalone instead.') . '</div>';
        }
    }

    #[\Override]
    public function footer(): void
    {
        echo '</div>';
        site_project_footer([]);
    }

    private function displayMenu(): void
    {
        $language_id = '';
        if (defined('DEFAULT_LANGUAGE')) {
            $language_id = DEFAULT_LANGUAGE;
        }
        switch ($language_id) {
            case 'fr_FR':
                $attatch_page     = 'DéposerUnFichier';
                $preferences_page = 'PréférencesUtilisateurs';
                break;
            case 'en_US':
            default:
                $attatch_page     = 'UpLoad';
                $preferences_page = 'UserPreferences';
                break;
        }

        $selected_tab = 'docs';
        if (isset($_REQUEST['view']) && $_REQUEST['view'] === 'browsePages') {
            $selected_tab = 'browsePages';
        }
        if (isset($_REQUEST['pagename'])) {
            $selected_tab = match ($_REQUEST['pagename']) {
                $attatch_page => 'attach',
                $preferences_page => 'prefs',
                default => 'docs',
            };
        }

        print '<div class="main-project-tabs">
    <nav class="tlp-tabs">
      <a href="' . $this->wikiLink . '" class="tlp-tab' . ($selected_tab === 'docs' ? ' tlp-tab-active' : '') . '">' . _('Wiki Documents') . '</a>
      <a href="' . $this->wikiLink . '&view=browsePages" data-test="wiki-browse-pages" class="tlp-tab' . ($selected_tab === 'browsePages' ? ' tlp-tab-active' : '') . '">' . $GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'menupages') . '</a>';
        if (UserManager::instance()->getCurrentUserWithLoggedInInformation()->is_logged_in) {
            print '<a href="' . $this->wikiLink . '&pagename=' . $attatch_page . '" class="tlp-tab' . ($selected_tab === 'attach' ? ' tlp-tab-active' : '') . '">' . $GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'menuattch') . '</a>';
            print '<a href="' . $this->wikiLink . '&pagename=' . $preferences_page . '" data-test="wiki-preferences" class="tlp-tab' . ($selected_tab === 'prefs' ? ' tlp-tab-active' : '') . '">' . $GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'menuprefs') . '</a>';
        }

        print '</nav>
        </div>';
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
        $this->renderPerms($postUrl);
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
    #[\Override]
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
