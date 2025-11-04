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

use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Layout\CssViteAsset;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\PHPWiki\WikiPage;

require_once __DIR__ . '/WikiViews.php';
require_once __DIR__ . '/../lib/WikiEntry.php';
require_once __DIR__ . '/../lib/WikiPage.php';
require_once __DIR__ . '/../lib/WikiPageWrapper.php';
require_once __DIR__ . '/../lib/WikiAttachment.php';
require_once __DIR__ . '/../../../www/project/admin/permissions.php';

/**
 * HTML display of Wiki Service Administration Panel
 *
 * This class is extended of View componnent, each function display a part of
 * Admin Panel of Wiki Service. You can call each function independently with
 * a GET method with 'view' option. (e.g. &view=phpWikiAdmin in URL will
 * display phpWikiAdmin function).
 * The mapping between Views and function is based on:
 * <pre>
 * Admin (main)
 * |-- Manage Wiki Documents (wikiDocuments)
 * |-- Manage Wiki Pages (wikiPages)
 * `-- Set Wiki Permissions (wikiPerms)
 * </pre>
 *
 */
class WikiServiceAdminViews extends WikiViews // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public function __construct(&$controler, $id = 0)
    {
        parent::__construct($controler, $id);
        $pm          = ProjectManager::instance();
        $this->title = $GLOBALS['Language']->getText('wiki_views_wkserviews', 'title', [$pm->getProject($this->gid)->getPublicName()]);
    }

    private function displayEntryForm($act = '', $id = '', $name = '', $page = '', $desc = '', $rank = ''): void
    {
        $purifier = Codendi_HTMLPurifier::instance();
        print '<form name="wikiEntry" method="post" action="' . $this->wikiAdminLink . '&view=wikiDocuments">
             <input type="hidden" name="group_id" value="' . $purifier->purify($this->gid) . '" />
             <input type="hidden" name="action" value="' . $purifier->purify($act) . '" />
             <input type="hidden" name="id" value="' . $purifier->purify($id) . '" />
           ';

        print '<div class="tlp-form-element">
             <label class="tlp-label" for="name">' . $GLOBALS['Language']->getText('wiki_views_wkserviews', 'entry_name') . '</label>
             <input type="text" name="name" id="name" value="' . $purifier->purify($name) . '" size="60" maxlength="255" class="tlp-input"/>
             <p class="tlp-text-info">' . $GLOBALS['Language']->getText('wiki_views_wkserviews', 'entry_em') . '</p>
           </div>';

        $allPages   = WikiPage::getAllUserPages();
        $allPages[] = '';

        $selectedPage = $purifier->purify($page);
        $upageValue   = '';
        if (! in_array($page, $allPages)) {
            $selectedPage = '';
            $upageValue   = $purifier->purify($page);
        }
        print '<div class="tlp-form-element">
            <div class="phpwiki-create-wiki-page">
                <div>
                    <label class="tlp-label" for="page">' . $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wikipage') . '</label>
                    ' . html_build_select_box_from_array($allPages, 'page', $selectedPage, true) . '
                </div>
                <div>
                    <label class="tlp-label" for="upage">' . $GLOBALS['Language']->getText('wiki_views_wkserviews', 'givename') . '</label>
                    <input type="text" name="upage" id="upage" value="' . $upageValue . '" size="20" maxlength="255" class="tlp-input" />
                </div>
            </div>
            <p class="tlp-text-info">' . $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wikipage_em', [$this->wikiAdminLink]) . '</p>
        </div>';

        print '<div class="tlp-form-element">
             <label class="tlp-label" for="desc">' . $GLOBALS['Language']->getText('wiki_views_wkserviews', 'description') . '</label>
             <textarea name="desc" id="desc" rows="5" cols="60" class="tlp-textarea">' . $purifier->purify($desc) . '</textarea>
             <p class="tlp-text-info">' . $GLOBALS['Language']->getText('wiki_views_wkserviews', 'description_em') . '</p>
           </div>';

        print '<div class="tlp-form-element">
             <label class="tlp-label" for="rank">' . $GLOBALS['Language']->getText('wiki_views_wkserviews', 'rank_screen') . '</label>
             <input type="text" name="rank" id="rank" value="' . $purifier->purify($rank) . '" size="3" maxlength="3" class="tlp-input" />
             <p class="tlp-text-info">' . $GLOBALS['Language']->getText('wiki_views_wkserviews', 'rank_screen_em') . '</p>
           </div>';

        $label = $GLOBALS['Language']->getText('wiki_views_wkserviews', 'act_create');
        if ($act === 'update') {
            $label = $GLOBALS['Language']->getText('wiki_views_wkserviews', 'act_update');
        }
        print '<div class="tlp-pane-section-submit">
             <input type="submit" class="tlp-button-primary" value="' . $label . '" />
           </div>';

        print '
           </form>';
    }

    #[\Override]
    public function header(): void
    {
        $breadcrumbs = new BreadCrumbCollection();
        $breadcrumbs->addBreadCrumb($this->getServiceCrumb());
        $breadcrumbs->addBreadCrumb(
            new BreadCrumb(
                new BreadCrumbLink(
                    $GLOBALS['Language']->getText('global', 'Administration'),
                    $this->wikiAdminLink,
                )
            )
        );
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
        echo '<h1 class="project-administration-title">PhpWiki Administration</h1>';
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
        $selected_tab = 'admin';
        if (isset($_REQUEST['view']) && ! is_null($_REQUEST['view'])) {
            $selected_tab = $_REQUEST['view'];
        }
        echo '<div class="main-project-tabs">';
        if (defined('DEFAULT_LANGUAGE') && DEFAULT_LANGUAGE === 'fr_FR') {
            print '
		     <nav class="tlp-tabs">
		       <a href="/wiki/admin/index.php?group_id=' . $this->gid . '" class="tlp-tab' . ($selected_tab === 'admin' ? ' tlp-tab-active' : '') . '">Admin</a>
		       <a href="' . $this->wikiAdminLink . '&view=wikiDocuments" class="tlp-tab' . (in_array($selected_tab, ['wikiDocuments', 'docPerms', 'updateWikiDocument'], true) ? ' tlp-tab-active' : '') . '">Documents Wiki</a>
		       <a href="' . $this->wikiAdminLink . '&view=wikiPages" class="tlp-tab' . ($selected_tab === 'wikiPages' ? ' tlp-tab-active' : '') . '">Pages Wiki</a>
		       <a href="' . $this->wikiAdminLink . '&view=wikiAttachments" class="tlp-tab' . ($selected_tab === 'wikiAttachments' ? ' tlp-tab-active' : '') . '">Fichiers joints</a>
		       <a href="' . $this->wikiAdminLink . '&view=wikiPerms" class="tlp-tab' . ($selected_tab === 'wikiPerms' ? ' tlp-tab-active' : '') . '">Permissions Wiki</a>
		     </nav>';
        } else {
            print '
		     <nav class="tlp-tabs">
		       <a href="/wiki/admin/index.php?group_id=' . $this->gid . '" class="tlp-tab' . ($selected_tab === 'admin' ? ' tlp-tab-active' : '') . '">Admin</a>
		       <a href="' . $this->wikiAdminLink . '&view=wikiDocuments" class="tlp-tab' . (in_array($selected_tab, ['wikiDocuments', 'docPerms', 'updateWikiDocument'], true) ? ' tlp-tab-active' : '') . '">Wiki Documents</a>
		       <a href="' . $this->wikiAdminLink . '&view=wikiPages" class="tlp-tab' . ($selected_tab === 'wikiPages' ? ' tlp-tab-active' : '') . '">Wiki Pages</a>
		       <a href="' . $this->wikiAdminLink . '&view=wikiAttachments" class="tlp-tab' . ($selected_tab === 'wikiAttachments' ? ' tlp-tab-active' : '') . '">Wiki Attachments</a>
		       <a href="' . $this->wikiAdminLink . '&view=wikiPerms" class="tlp-tab' . ($selected_tab === 'wikiPerms' ? ' tlp-tab-active' : '') . '">Wiki Permissions</a>
		     </nav>';
        }
        echo '</div>';
    }

  /**
   * main - public View
   *
   * Main and default view.
   */
    #[\Override]
    public function main()
    {
        echo '<section class="tlp-pane">
            <div class="tlp-pane-container">
                <div class="tlp-pane-header">
                    <h1 class="tlp-pane-title">Wiki Administration</h1>
                </div>
                <div class="tlp-pane-section">
                    <ul>';
        if (defined('DEFAULT_LANGUAGE') && DEFAULT_LANGUAGE === 'fr_FR') {
            printf('<li><a href=%s&view=wikiDocuments>Gérer les documents Wiki</a><p>Créer, supprimer, modifier et donner des permissions sur des documents Wiki.</p></li>', $this->wikiAdminLink);
            printf('<li><a href=%s&view=wikiPages>Gérer les pages Wiki</a><p>Parcourir et donner des permissions sur des pages Wiki.</p></li>', $this->wikiAdminLink);
            printf('<li><a href=%s&view=wikiAttachments>Gérer les fichiers joints</a><p>Parcourir et définir les permissions des fichiers joints au Wiki</p></li>', $this->wikiAdminLink);
            printf('<li><a href=%s&view=wikiPerms>Gérer les permissions Wiki</a><p>Donner des permissions sur tout le Wiki %s.</p></li>', $this->wikiAdminLink, $this->wikiname);
            printf("<li><a href=%s&pagename=AdministrationDePhpWiki>Administration du wiki</a><p>Panneau d'administration de l'engin wiki. Plusieurs outils pour suppression , renommage et réinitialisation de pages.</p>", $this->wikiLink);
        } else {
            printf('<li><a href= %s&view=wikiDocuments data-test="manage-wiki-documents">Manage Wiki Documents</a><p>Create, delete, modify and set specific permissions on Wiki Documents.</p></li>', $this->wikiAdminLink);
            printf("<li><a data-test='manage-wiki-page' href=%s&view=wikiPages>Manage Wiki Pages</a><p>Browse and set specific permissions on Wiki Pages.</p>", $this->wikiAdminLink);
            printf('<li><a href=%s&view=wikiAttachments>Manage Wiki Attachments</a><p>Browse and set permissions on ressources attached on the Wiki.</p></li>', $this->wikiAdminLink);
            printf('<li><a href=%s&view=wikiPerms data-test="set-wiki-permissions">Set Wiki Permissions</a><p>Set permissions on whole %s Wiki.</p></li>', $this->wikiAdminLink, $this->wikiname);
            printf('<li><a href=%s&pagename=PhpWikiAdministration>PhpWiki Administration</a><p>Administration panel of the wiki engine. This propose a set of tools to delete and rename pages.</p></li>', $this->wikiLink);
        }
        echo '      </ul>
                </div>
            </div>
        </section>';
    }

    public function wikiDocuments()
    {
        $this->createWikiDocument();
        $this->browseWikiDocument();
    }

    private function createWikiDocument(): void
    {
        $title = $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wikidocs_create');
        echo <<<EOT
        <section class="tlp-pane">
            <div class="tlp-pane-container">
                <div class="tlp-pane-header">
                    <h1 class="tlp-pane-title">$title</h1>
                </div>
                <section class="tlp-pane-section">
        EOT;
        $this->displayEntryForm('create');
        echo <<<EOT
                </section>
            </div>
        </section>
        EOT;
    }

    private function browseWikiDocument(): void
    {
        $purifier = Codendi_HTMLPurifier::instance();
        $wei      = WikiEntry::getEntryIterator($this->gid);

        echo '
        <section class="tlp-pane">
            <div class="tlp-pane-container">
                <div class="tlp-pane-header">
                    <h1 class="tlp-pane-title">' . $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wikidocs_docs') . '</h1>
                </div>
                <section class="tlp-pane-section">
        ';
        echo $GLOBALS['Language']->getText('wiki_views_wkserviews', 'browsedoc');

        echo '
        <table class="tlp-table" data-test="table-test">
            <thead>
                <tr>
                    <th>' . $GLOBALS['Language']->getText('wiki_views_wkserviews', 'doc_name') . '</th>
                    <th>' . $GLOBALS['Language']->getText('wiki_views_wkserviews', 'doc_page') . '</th>
                    <th>' . $GLOBALS['Language']->getText('wiki_views_wkserviews', 'doc_rank') . '</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>';

        if (count($wei) === 0) {
            echo '<tr><td colspan="4" class="tlp-table-cell-empty">' . _('There is no Wiki Document yet') . '</td></tr>';
        }

        while ($wei->valid()) {
            $we = $wei->current();

            print '<tr>';

            print '<td>
               <a href="' . $this->wikiAdminLink . '&view=updateWikiDocument&id=' . $we->getId() . '">' . $purifier->purify($we->getName()) . '</a>
            </td>';

            print '<td>';
            print $purifier->purify($we->getPage());
            print ' - ';
            print '<a href="' . $this->wikiAdminLink . '&view=docPerms&id=' . $we->wikiPage->getId() . '">';
            $status = $GLOBALS['Language']->getText('wiki_views_wkserviews', 'define_perms');
            if ($we->wikiPage->permissionExist()) {
                $status = $GLOBALS['Language']->getText('wiki_views_wkserviews', 'edit_perms');
            }
            print '[' . $purifier->purify($status) . ']';
            print '</a>';
            print '</td>';

            print '<td>' . $we->getRank() . '</td>';

            print '<td class="tlp-table-cell-actions">';

            $alt = $GLOBALS['Language']->getText('wiki_views_wkserviews', 'deletedoc', [$purifier->purify($we->getName())]);
            print '
                <a href="' . $this->wikiAdminLink . '&view=wikiDocuments&action=delete&id=' . $we->getId() . '"
                    title="' . $GLOBALS['Language']->getText('common_mvc_view', 'warn', $alt) . '"
                    class="tlp-button-danger tlp-button-outline tlp-button-small tlp-table-cell-actions-button"
                >
                    <i class="fa-regular fa-trash-alt tlp-button-icon" aria-hidden="true"></i>
                    ' . _('Delete') . '
                </a>';
            print '</td>';

            print '</tr>';

            $wei->next();
        }
        print '</tbody></table>';
        echo <<<EOT
                </section>
            </div>
        </section>
        EOT;
    }

  /**
   * updateWikiDocument - public View
   */
    public function updateWikiDocument()
    {
        print $GLOBALS['Language']->getText('wiki_views_wkserviews', 'updatedoc', [$this->wikiname]);

        $we = new WikiEntry($_REQUEST['id']);
        $this->displayEntryForm(
            'update',
            $we->getId(),
            $we->getName(),
            $we->getPage(),
            $we->getDesc(),
            $we->getRank()
        );
        print '<p><a href="' . $this->wikiAdminLink . '&view=wikiDocuments">' . $GLOBALS['Language']->getText('global', 'back') . '</a></p>' . "\n";
    }

  /**
   * This function is a "false" document permission view. Actually,
   * it set permission on a page. This function only exist to make an
   * auto return on wikiDocuments view after permission settings.
   *
   *
   * pagePerms - public View
   */
    public function docPerms()
    {
        $postUrl = '/wiki/admin/index.php?group_id=' . $this->gid . '&view=wikiDocuments&action=setWikiPagePerms';
        $this->renderPerms($postUrl);
        print '<p><a href="' . $this->wikiAdminLink . '&view=wikiPages"' . $GLOBALS['Language']->getText('global', 'back') . '</a></p>' . "\n";
    }

  /**
   * pagePerms - public View
   */
    public function pagePerms()
    {
        $postUrl = '/wiki/admin/index.php?group_id=' . $this->gid . '&view=wikiPages&action=setWikiPagePerms';
        $this->renderPerms($postUrl);
        print '<p><a href="' . $this->wikiAdminLink . '&view=wikiPages">' . $GLOBALS['Language']->getText('global', 'back') . '</a></p>' . "\n";
    }

    public function wikiPages()
    {
        echo '
        <section class="tlp-pane">
            <div class="tlp-pane-container">
                <div class="tlp-pane-header">
                    <h1 class="tlp-pane-title">' . $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wkpage_project') . '</h1>
                </div>
                <section class="tlp-pane-section">
        ';
        echo $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wkpage_pj_all');
        $this->browsePages(WikiPage::getAllUserPages());
        echo <<<EOT
                </section>
            </div>
        </section>
        EOT;

        echo '
        <section class="tlp-pane">
            <div class="tlp-pane-container">
                <div class="tlp-pane-header">
                    <h1 class="tlp-pane-title">' . $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wkpage_empty') . '</h1>
                </div>
                <section class="tlp-pane-section">
        ';
        echo $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wkpage_emp_all');
        $this->browsePages(new WikiPageWrapper($this->gid)->getProjectEmptyLinks());
        echo <<<EOT
                </section>
            </div>
        </section>
        EOT;

        echo '
        <section class="tlp-pane">
            <div class="tlp-pane-container">
                <div class="tlp-pane-header">
                    <h1 class="tlp-pane-title">' . $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wkpage_admin') . '</h1>
                </div>
                <section class="tlp-pane-section">
        ';
        echo $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wkpage_adm_all');
        $this->browsePages(WikiPage::getAllAdminPages());
        echo <<<EOT
                </section>
            </div>
        </section>
        EOT;

        echo '
        <section class="tlp-pane">
            <div class="tlp-pane-container">
                <div class="tlp-pane-header">
                    <h1 class="tlp-pane-title">' . $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wkpage_intern') . '</h1>
                </div>
                <section class="tlp-pane-section">
        ';
        echo $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wkpage_int_all');
        $this->browsePages(WikiPage::getAllInternalPages());
        echo <<<EOT
                </section>
            </div>
        </section>
        EOT;
    }

    private function browsePages(array $pageList): void
    {
        echo '
        <table class="tlp-table" data-test="table-test">
            <thead>
                <tr>
                    <th>Page</th>
                    <th>Permissions</th>
                </tr>
            </thead>
            <tbody>';

        if (count($pageList) === 0) {
            echo '<tr><td colspan="2" class="tlp-table-cell-empty">' . _('There is no page to display here') . '</td></tr>';
        }

        $purifier = Codendi_HTMLPurifier::instance();
        sort($pageList);
        foreach ($pageList as $pagename) {
            print '';

            print '<td><a href="' . $this->wikiLink . '&pagename=' . urlencode($pagename) . '">' . $purifier->purify($pagename) . '</a></td>';

            $page   = new WikiPage($this->gid, $pagename);
            $status = $GLOBALS['Language']->getText('wiki_views_wkserviews', 'define_perms');
            if (permission_exist('WIKIPAGE_READ', $page->getId())) {
                $status = $GLOBALS['Language']->getText('wiki_views_wkserviews', 'edit_perms');
            }
            $eM         = EventManager::instance();
            $referenced = false;
            $eM->processEvent('isWikiPageReferenced', [
                'referenced' => &$referenced,
                'wiki_page'  => $pagename,
                'group_id' => $this->gid,
            ]);

            print '<td>';

            if ($referenced) {
                $label = '';
                $eM->processEvent('getPermsLabelForWiki', [
                    'label'  => &$label,
                ]);
                print $purifier->purify($label);
            } else {
                print '<a href="' . $this->wikiAdminLink . '&view=pagePerms&id=' . urlencode($page->getId()) . '">[' . $purifier->purify($status) . ']</a>';
            }

            print '</td>';

            print '</tr>';
        }
        print '</tbody></table>';
    }

    public function wikiPerms(): void
    {
        $purifier = Codendi_HTMLPurifier::instance();
        echo '
        <section class="tlp-pane">
            <div class="tlp-pane-container">
                <section class="tlp-pane-section">
        ';
        echo $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wikiperm');
        $postUrl = '/wiki/admin/index.php?group_id=' . $purifier->purify(urlencode((string) $this->gid)) . '&action=setWikiPerms';
        permission_display_selection_form('WIKI_READ', $this->gid, $this->gid, $postUrl);
        echo <<<EOT
                </section>
            </div>
        </section>
        EOT;
    }

    /**
     * @access public
     */
    public function wikiAttachments()
    {
        $purifier = Codendi_HTMLPurifier::instance();
        echo '
        <section class="tlp-pane">
            <div class="tlp-pane-container">
                <section class="tlp-pane-section">
        ';
        print '<form method="post" action="' . $this->wikiAdminLink . '&view=wikiAttachments&action=deleteAttachments">';
        echo '
        <table class="tlp-table">
            <thead>
                <tr>
                    <th></th>
                    <th>' . $GLOBALS['Language']->getText('wiki_views_wkserviews', 'attachment_revisions') . '</th>
                    <th>' . $GLOBALS['Language']->getText('wiki_views_wkserviews', 'attachment_permissions') . '</th>
                    <th>' . $GLOBALS['Language']->getText('wiki_views_wkserviews', 'attachment_delete') . ' ?</th>
                </tr>
            </thead>
            <tbody>';

        $attachments = WikiAttachment::getAttachmentIterator($this->gid);
        if (count($attachments) === 0) {
            echo '<tr><td colspan="4" class="tlp-table-cell-empty">' . _('There is no attachment') . '</td>';
        } else {
            foreach ($attachments as $wa) {
                if ($wa->isActive()) {
                    print '<tr>';

                    $filename = basename($wa->getFilename());
                    $id       = $wa->getId();

                    print '<td><a href="' . $this->wikiAdminLink . '&view=browseAttachment&id=' . urlencode($id) . '">' . $purifier->purify($filename) . '</a></td>';
                    print '<td>' . $wa->count() . '</td>';

                    $status = $GLOBALS['Language']->getText('wiki_views_wkserviews', 'define_perms');
                    if ($wa->permissionExist()) {
                        $status = $GLOBALS['Language']->getText('wiki_views_wkserviews', 'edit_perms');
                    }
                    print '<td>';
                    print '<a href="' . $this->wikiAdminLink . '&view=attachmentPerms&id=' . urlencode($id) . '">[' . $purifier->purify($status) . ']</a>';
                    print '</td>';

                    print '<td>';
                    print '<input type="checkbox" value="' . $wa->getId() . '" name="attachments_to_delete[]">';
                    print '</td>';
                    print '</tr>';
                }
            }
            print '<tr><td colspan="3"></td><td><input type="submit" class="tlp-table-cell-actions-button tlp-button-danger tlp-button-small tlp-button-outline" value="' . $GLOBALS['Language']->getText('wiki_views_wkserviews', 'attachment_delete') . '"></td></tr>';
        }
        print '</tbody></table>';
        print '</form>';
        echo <<<EOT
                </section>
            </div>
        </section>
        EOT;
    }

    public function attachmentPerms()
    {
        $attachmentId = $_GET['id'];

        $wa = new WikiAttachment($this->gid);
        $wa->initWithId($attachmentId);

        $purifier = Codendi_HTMLPurifier::instance();

        echo $GLOBALS['Language']->getText('wiki_views_wkserviews', 'perm_attachment_title', [$purifier->purify($this->wikiname)]);

        echo $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wiki_attachment_perm', [$purifier->purify($wa->getFilename())]);

        $postUrl = $this->wikiAdminLink . '&view=wikiAttachments&action=setWikiAttachmentPerms';
        permission_display_selection_form('WIKIATTACHMENT_READ', $wa->getId(), $this->gid, $postUrl);

        print '<hr/><p><a href="' . $this->wikiAdminLink . '&view=wikiAttachments">' . $GLOBALS['Language']->getText('wiki_views_wkserviews', 'back_admin') . '</a></p>' . "\n";
    }

    /**
     * @access public
     */
    public function browseAttachment()
    {
        $attachmentId = (int) $_GET['id'];

        $wa = new WikiAttachment($this->gid);
        $wa->initWithId($attachmentId);

        $purifier = Codendi_HTMLPurifier::instance();

        echo $GLOBALS['Language']->getText('wiki_views_wkserviews', 'browse_attachment_title', [$purifier->purify($this->wikiname), $purifier->purify($wa->getFilename())]);

        // if($wari->exist()) {
        print html_build_list_table_top([$GLOBALS['Language']->getText('wiki_views_wkserviews', 'attachment_revision'),
            $GLOBALS['Language']->getText('wiki_views_wkserviews', 'attachment_date'),
            $GLOBALS['Language']->getText('wiki_views_wkserviews', 'attachment_author'),
            $GLOBALS['Language']->getText('wiki_views_wkserviews', 'attachment_size'),
        ]);
        $wari = WikiAttachmentRevision::getRevisionIterator($this->gid, $attachmentId);
        $wari->rewind();
        while ($wari->valid()) {
            $war = $wari->current();

            print '
             <tr>
	       <td><a href="/wiki/uploads/' . $purifier->purify(urlencode((string) $this->gid)) . '/' . $purifier->purify(urlencode($wa->getFilename())) . '/' . $purifier->purify(urlencode($war->getRevision() + 1)) . '">' . $purifier->purify(urlencode($war->getRevision() + 1)) . '</a></td>
	       <td>' . $purifier->purify(strftime('%e %b %Y %H:%M', $war->getDate())) . '</td>
               <td><a href="/users/' . $purifier->purify(urlencode(user_getname($war->getOwnerId()))) . '/">' . $purifier->purify(user_getname($war->getOwnerId())) . '</td>
	       <td>' . $purifier->purify($war->getSize()) . '</td>
	     </tr>';

            $wari->next();
        }

        print '</table>';
        // }
        // else {
        //   print 'not found';
        // }
        print '<hr/><p><a href="' . $this->wikiAdminLink . '&view=wikiAttachments">' . $GLOBALS['Language']->getText('wiki_views_wkserviews', 'back_admin') . '</a></p>' . "\n";
    }

    public function install()
    {
        $wpw = new WikiPageWrapper($this->gid);
        $wpw->install();
    }

    public function upgrade()
    {
        $wpw = new WikiPageWrapper($this->gid);

        $nbGroupPending = null;
        $nextId         = $wpw->getNextGroupWithWiki($this->gid, $nbGroupPending);

        $purifier = Codendi_HTMLPurifier::instance();

        $html = 'Nb project to go: ' . $purifier->purify($nbGroupPending) . '<br>';

        $url   = $purifier->purify('/wiki/admin/index.php?group_id=' . urlencode($nextId) . '&view=upgrade');
        $href  = '<a href="' . $url . '">' . $purifier->purify($nextId) . '</a>';
        $html .= 'Next project: ' . $href . '<br>';

        print $html;

        $wpw->upgrade();
    }
}
