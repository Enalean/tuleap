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

require_once __DIR__ . '/WikiViews.class.php';
require_once __DIR__ . '/../lib/WikiEntry.class.php';
require_once __DIR__ . '/../lib/WikiPage.class.php';
require_once __DIR__ . '/../lib/WikiPageWrapper.class.php';
require_once __DIR__ . '/../lib/WikiAttachment.class.php';
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
class WikiServiceAdminViews extends WikiViews
{
  /**
   * WikiServiceAdminViews - Constructor
   */
    public function __construct(&$controler, $id = 0)
    {
        parent::WikiView($controler, $id);
        $pm          = ProjectManager::instance();
        $this->title = $GLOBALS['Language']->getText('wiki_views_wkserviews', 'title', [$pm->getProject($this->gid)->getPublicName()]);
    }

  /**
   * displayEntryForm - private
   */
    public function _displayEntryForm($act = '', $id = '', $name = '', $page = '', $desc = '', $rank = '')
    {
        $purifier = Codendi_HTMLPurifier::instance();
        print '<form name="wikiEntry" method="post" action="' . $this->wikiAdminLink . '&view=wikiDocuments">
             <input type="hidden" name="group_id" value="' . $purifier->purify($this->gid) . '" />
             <input type="hidden" name="action" value="' . $purifier->purify($act) . '" />
             <input type="hidden" name="id" value="' . $purifier->purify($id) . '" />
           <table>';

        print '<tr>
             <td>' . $GLOBALS['Language']->getText('wiki_views_wkserviews', 'entry_name') . '</td>
             <td ><input type="text" name="name" value="' . $purifier->purify($name) . '" size="60" maxlength="255"/></td>
             <td>' . $GLOBALS['Language']->getText('wiki_views_wkserviews', 'entry_em') . '</td>
           </tr>';

        $allPages   = WikiPage::getAllUserPages();
        $allPages[] = '';

        $selectedPage = $purifier->purify($page);
        $upageValue   = '';
        if (! in_array($page, $allPages)) {
            $selectedPage = '';
            $upageValue   = $purifier->purify($page);
        }
        print '<tr>
             <td>' . $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wikipage') . '</td>
             <td>
               ' . html_build_select_box_from_array($allPages, 'page', $selectedPage, true) . '<br />' . $GLOBALS['Language']->getText('wiki_views_wkserviews', 'givename') . ' <input type="text" name="upage" value="' . $upageValue . '" size="20" maxlength="255"/>
             </td>
             <td>' . $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wikipage_em', [$this->wikiAdminLink]) . '</td>
           </tr>';

        print '<tr>
             <td>' . $GLOBALS['Language']->getText('wiki_views_wkserviews', 'description') . '</td>
             <td><textarea name="desc" rows="5" cols="60">' . $purifier->purify($desc) . '</textarea></td>
             <td>' . $GLOBALS['Language']->getText('wiki_views_wkserviews', 'description_em') . '</td>
           </tr>';

        print '<tr>
             <td>' . $GLOBALS['Language']->getText('wiki_views_wkserviews', 'rank_screen') . '</td>
             <td><input type="text" name="rank" value="' . $purifier->purify($rank) . '" size="3" maxlength="3"/></td>
             <td>' . $GLOBALS['Language']->getText('wiki_views_wkserviews', 'rank_screen_em') . '</td>
           </tr>';

        $label = $GLOBALS['Language']->getText('wiki_views_wkserviews', 'act_create');
        if ($act === 'update') {
            $label = $GLOBALS['Language']->getText('wiki_views_wkserviews', 'act_update');
        }
        print '<tr>
             <td colspan="3"><input type="submit" value="' . $label . '" /></td>
           </tr>';

        print '</table>
           </form>';
    }

  /**
   * displayMenu - public
   */
    public function displayMenu()
    {
        if (defined('DEFAULT_LANGUAGE') && DEFAULT_LANGUAGE === 'fr_FR') {
            print '
		     <ul class="ServiceMenu">
		       <li><a href="/wiki/index.php?group_id=' . $this->gid . '">Parcourir</a>&nbsp;|&nbsp;</li>
		       <li><a href="/wiki/admin/index.php?group_id=' . $this->gid . '">Admin</a>&nbsp;|&nbsp;</li>
		       <li><a href="' . $this->wikiAdminLink . '&view=wikiDocuments">Documents Wiki</a>&nbsp;|&nbsp;</li>
		       <li><a href="' . $this->wikiAdminLink . '&view=wikiPages">Pages Wiki</a>&nbsp;|&nbsp;</li>
		       <li><a href="' . $this->wikiAdminLink . '&view=wikiAttachments">Fichiers joints</a>&nbsp;|&nbsp;</li>
		       <li><a href="' . $this->wikiAdminLink . '&view=wikiPerms">Permissions Wiki</a>&nbsp;|&nbsp;</li>
		     </ul>';
        } else {
            print '
		     <ul class="ServiceMenu">
		       <li><a href="/wiki/index.php?group_id=' . $this->gid . '">View</a>&nbsp;|&nbsp;</li>
		       <li><a href="/wiki/admin/index.php?group_id=' . $this->gid . '">Admin</a>&nbsp;|&nbsp;</li>
		       <li><a href="' . $this->wikiAdminLink . '&view=wikiDocuments">Wiki Documents</a>&nbsp;|&nbsp;</li>
		       <li><a href="' . $this->wikiAdminLink . '&view=wikiPages">Wiki Pages</a>&nbsp;|&nbsp;</li>
		       <li><a href="' . $this->wikiAdminLink . '&view=wikiAttachments">Wiki Attachments</a>&nbsp;|&nbsp;</li>
		       <li><a href="' . $this->wikiAdminLink . '&view=wikiPerms">Wiki Permissions</a>&nbsp;|&nbsp;</li>
		     </ul>';
        }
    }

  /**
   * main - public View
   *
   * Main and default view.
   */
    public function main()
    {
        if (defined('DEFAULT_LANGUAGE') && DEFAULT_LANGUAGE === 'fr_FR') {
            printf("<h2>Wiki %s - Administration</h2><h3><a href=%s&view=wikiDocuments>Gérer les documents Wiki</a></h3><p>Créer, supprimer, modifier et donner des permissions sur des documents Wiki.</p>", $this->wikiname, $this->wikiAdminLink);
            printf("<h3><a href=%s&view=wikiPages>Gérer les pages Wiki</a></h3><p>Parcourir et donner des permissions sur des pages Wiki.</p>", $this->wikiAdminLink);
            printf("<h3><a href=%s&view=wikiAttachments>Gérer les fichiers joints</a></h3><p>Parcourir et définir les permissions des fichiers joints au Wiki</p>", $this->wikiAdminLink);
            printf("<h3><a href=%s&view=wikiPerms>Gérer les permissions Wiki</a></h3><p>Donner des permissions sur tout le Wiki %s.</p>", $this->wikiAdminLink, $this->wikiname);
            printf("<h3><a href=%s&pagename=AdministrationDePhpWiki>Administration du wiki</a></h3><p>Panneau d'administration de l'engin wiki. Plusieurs outils pour suppression , renommage et réinitialisation de pages.</p>", $this->wikiLink);
        } else {
            printf("<h2>Wiki  %s - Administration</h2><h3><a href= %s&view=wikiDocuments data-test=\"manage-wiki-documents\">Manage Wiki Documents</a></h3><p>Create, delete, modify and set specific permissions on Wiki Documents.</p>", $this->wikiname, $this->wikiAdminLink);
            printf("<h3><a data-test='manage-wiki-page' href=%s&view=wikiPages>Manage Wiki Pages</a></h3><p>Browse and set specific permissions on Wiki Pages.</p>", $this->wikiAdminLink);
            printf("<h3><a href=%s&view=wikiAttachments>Manage Wiki Attachments</a></h3><p>Browse and set permissions on ressources attached on the Wiki.</p>", $this->wikiAdminLink);
            printf("<h3><a href=%s&view=wikiPerms data-test=\"set-wiki-permissions\">Set Wiki Permissions</a></h3><p>Set permissions on whole %s Wiki.</p>", $this->wikiAdminLink, $this->wikiname);
            printf("<h3><a href=%s&pagename=PhpWikiAdministration>PhpWiki Administration</a></h3><p>Administration panel of the wiki engine. This propose a set of tools to delete and rename pages.</p>", $this->wikiLink);
        }
    }

  /**
   * wikiDocuments - public view
   */
    public function wikiDocuments()
    {
        echo $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wikidocs_title', [$this->wikiname]);

        list($hideFlag, $hideUrl, $hideImg) = hide_url('wiki_admin_createdoc', $this->gid);
        $hurl                               = '<a href="' . $this->wikiAdminLink . '&view=wikiDocuments&' . $hideUrl . '">' . $hideImg . '</a>';
        print $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wikidocs_create', [$hurl]);
        if (! $hideFlag) {
            $this->_createWikiDocument();
        }

      //    print "\n<hr/>\n";
        list($hideFlag, $hideUrl, $hideImg) = hide_url('wiki_admin_browsedoc', $this->gid);
        $hurl                               = '<a href="' . $this->wikiAdminLink . '&view=wikiDocuments&' . $hideUrl . '">' . $hideImg . '</a>';
        print $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wikidocs_docs', [$hurl]);
        if (! $hideFlag) {
            $this->_browseWikiDocument();
        }

        print '<hr/><p><a href="' . $this->wikiAdminLink . '">' . $GLOBALS['Language']->getText('wiki_views_wkserviews', 'back_admin') . '</a></p>' . "\n";
    }

  /**
   * _createWikiDocument - private
   */
    public function _createWikiDocument()
    {
        print $GLOBALS['Language']->getText('wiki_views_wkserviews', 'createwikidoc', [$this->wikiLink]);
        $this->_displayEntryForm('create');
    }

  /**
   * _browseWikiDocument - private
   */
    public function _browseWikiDocument()
    {
        $purifier = Codendi_HTMLPurifier::instance();
        $wei      = WikiEntry::getEntryIterator($this->gid);

        print $GLOBALS['Language']->getText('wiki_views_wkserviews', 'browsedoc');

        print html_build_list_table_top([$GLOBALS['Language']->getText('wiki_views_wkserviews', 'doc_name'),
            $GLOBALS['Language']->getText('wiki_views_wkserviews', 'doc_page'),
            $GLOBALS['Language']->getText('wiki_views_wkserviews', 'doc_rank'),
            $GLOBALS['Language']->getText('wiki_views_wkserviews', 'doc_delete'),
        ]);
        $i = 0;
        while ($wei->valid()) {
            $we = $wei->current();

            print '<tr class="' . html_get_alt_row_color($i) . '">';

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

            print '<td align="center">' . $we->getRank() . '</td>';

            print '<td align="center">';

            $alt = $GLOBALS['Language']->getText('wiki_views_wkserviews', 'deletedoc', [$purifier->purify($we->getName())]);
            print html_trash_link(
                $this->wikiAdminLink . '&view=wikiDocuments&action=delete&id=' . $we->getId(),
                $GLOBALS['Language']->getText('common_mvc_view', 'warn', $alt),
                $alt
            );
            print '</td>';

            print '</tr>';

            $i++;
            $wei->next();
        }
        print '</table>';
    }

  /**
   * updateWikiDocument - public View
   */
    public function updateWikiDocument()
    {
        print $GLOBALS['Language']->getText('wiki_views_wkserviews', 'updatedoc', [$this->wikiname]);

        $we = new WikiEntry($_REQUEST['id']);
        $this->_displayEntryForm(
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
        $this->_pagePerms($postUrl);
        print '<p><a href="' . $this->wikiAdminLink . '&view=wikiPages"' . $GLOBALS['Language']->getText('global', 'back') . '</a></p>' . "\n";
    }

  /**
   * pagePerms - public View
   */
    public function pagePerms()
    {
        $postUrl = '/wiki/admin/index.php?group_id=' . $this->gid . '&view=wikiPages&action=setWikiPagePerms';
        $this->_pagePerms($postUrl);
        print '<p><a href="' . $this->wikiAdminLink . '&view=wikiPages">' . $GLOBALS['Language']->getText('global', 'back') . '</a></p>' . "\n";
    }

  /**
   * wikiPages - public View
   */
    public function wikiPages()
    {
        echo $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wkpage_title', [$this->wikiname]);

        list($hideFlag, $hideUrl, $hideImg) = hide_url('wiki_project_pages', $this->gid);
        $hurl                               = '<a href="' . $this->wikiAdminLink . '&view=wikiPages&' . $hideUrl . '">' . $hideImg . '</a>';
        echo $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wkpage_project', [$hurl]);
        if (! $hideFlag) {
            print $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wkpage_pj_all');
            $allUserPages = WikiPage::getAllUserPages();
            $this->_browsePages($allUserPages);
        }

        list($hideFlag, $hideUrl, $hideImg) = hide_url('wiki_empty_pages', $this->gid);
        $hurl                               = '<a href="' . $this->wikiAdminLink . '&view=wikiPages&' . $hideUrl . '">' . $hideImg . '</a>';
        echo $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wkpage_empty', [$hurl]);
        if (! $hideFlag) {
            print $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wkpage_emp_all');
            $wpw           = new WikiPageWrapper($this->gid);
            $allEmptyPages = $wpw->getProjectEmptyLinks();
            $this->_browsePages($allEmptyPages);
        }

        list($hideFlag, $hideUrl, $hideImg) = hide_url('wiki_admin_pages', $this->gid);
        $hurl                               = '<a href="' . $this->wikiAdminLink . '&view=wikiPages&' . $hideUrl . '">' . $hideImg . '</a>';
        echo $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wkpage_admin', [$hurl]);
        if (! $hideFlag) {
            print $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wkpage_adm_all');
            $allAdminPages = WikiPage::getAllAdminPages();
            $this->_browsePages($allAdminPages);
        }

        list($hideFlag, $hideUrl, $hideImg) = hide_url('wiki_internal_pages', $this->gid, true);
        $hurl                               = '<a href="' . $this->wikiAdminLink . '&view=wikiPages&' . $hideUrl . '">' . $hideImg . '</a>';
        echo $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wkpage_intern', [$hurl]);
        if (! $hideFlag) {
            print $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wkpage_int_all');
            $allInternalsPages = WikiPage::getAllInternalPages();
            $this->_browsePages($allInternalsPages);
        }

        print '<hr/><p><a href="' . $this->wikiAdminLink . '">' . $GLOBALS['Language']->getText('wiki_views_wkserviews', 'back_admin') . '</a></p>' . "\n";
    }

    /**
    * browsePages - private
    */
    public function _browsePages(&$pageList)
    {
        print html_build_list_table_top(['Page', 'Permissions']);

        $purifier = Codendi_HTMLPurifier::instance();
        sort($pageList);
        $i = 0;
        foreach ($pageList as $pagename) {
            print '            <tr class="' . html_get_alt_row_color($i) . '">            ';

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

            print '<td align="center">';

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

            print '            </tr>            ';

            $i++;
        }
        print '</TABLE>';
    }

  /**
   * wikiPerms - public View
   */
    public function wikiPerms()
    {
        $purifier = Codendi_HTMLPurifier::instance();
        echo $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wikiperm', [$purifier->purify($this->wikiname), $purifier->purify($this->wikiname)]);
        $postUrl = '/wiki/admin/index.php?group_id=' . $purifier->purify(urlencode((string) $this->gid)) . '&action=setWikiPerms';
        permission_display_selection_form("WIKI_READ", $this->gid, $this->gid, $postUrl);

        print '<hr/><p><a href="' . $this->wikiAdminLink . '">' . $GLOBALS['Language']->getText('wiki_views_wkserviews', 'back_admin') . '</a></p>' . "\n";
    }

    /**
     * @access public
     */
    public function wikiAttachments()
    {
        $purifier = Codendi_HTMLPurifier::instance();
        echo $GLOBALS['Language']->getText('wiki_views_wkserviews', 'attachment_title', [$purifier->purify($this->wikiname)]);
        print '<form method="post" action="' . $this->wikiAdminLink . '&view=wikiAttachments&action=deleteAttachments">';
        print html_build_list_table_top([$GLOBALS['Language']->getText('wiki_views_wkserviews', 'attachment_name'),
            $GLOBALS['Language']->getText('wiki_views_wkserviews', 'attachment_revisions'),
            $GLOBALS['Language']->getText('wiki_views_wkserviews', 'attachment_permissions'),
            $GLOBALS['Language']->getText('wiki_views_wkserviews', 'attachment_delete') . " ?",
        ]);

        $wai = WikiAttachment::getAttachmentIterator($this->gid);
        $wai->rewind();
        while ($wai->valid()) {
            $wa = $wai->current();

            if ($wa->isActive()) {
                print '<tr>';

                $filename = basename($wa->getFilename());
                $id       = $wa->getId();

                print '<td><a href="' . $this->wikiAdminLink . '&view=browseAttachment&id=' . urlencode($id) . '">' . $purifier->purify($filename) . '</a></td>';
                print '<td align="center">' . $wa->count() . '</td>';

                $status = $GLOBALS['Language']->getText('wiki_views_wkserviews', 'define_perms');
                if ($wa->permissionExist()) {
                    $status = $GLOBALS['Language']->getText('wiki_views_wkserviews', 'edit_perms');
                }
                print '<td align="center">';
                print '<a href="' . $this->wikiAdminLink . '&view=attachmentPerms&id=' . urlencode($id) . '">[' . $purifier->purify($status) . ']</a>';
                print '</td>';

                print '<td align="center">';
                print '<input type="checkbox" value="' . $wa->getId() . '" name="attachments_to_delete[]">';
                print '</td>';
                print '</tr>';
            }

            $wai->next();
        }
        print '<td align="right" colspan="4" style="padding-right:50px; "><input type="submit" value="' . $GLOBALS['Language']->getText('wiki_views_wkserviews', 'attachment_delete') . '"></td></tr>';
        print '</table>';
        print '<hr/><p><a href="' . $this->wikiAdminLink . '">' . $GLOBALS['Language']->getText('wiki_views_wkserviews', 'back_admin') . '</a></p>' . "\n";
        print '</form>';
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
        permission_display_selection_form("WIKIATTACHMENT_READ", $wa->getId(), $this->gid, $postUrl);

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
	       <td>' . $purifier->purify(strftime("%e %b %Y %H:%M", $war->getDate())) . '</td>
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
