<?php
/* 
 * Copyright 2005, STMicroelectronics
 *
 * Originally written by Manuel Vacelet
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once('WikiViews.class.php');
require_once(dirname(__FILE__).'/../lib/WikiEntry.class.php');
require_once(dirname(__FILE__).'/../lib/WikiPage.class.php');
require_once(dirname(__FILE__).'/../lib/WikiPageWrapper.class.php');
require_once(dirname(__FILE__).'/../lib/WikiAttachment.class.php');
require_once('www/project/admin/permissions.php');

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
 * @package    WikiService
 * @subpackage WikiServiceAdmin
 * @copyright  STMicroelectronics, 2005
 * @author     Manuel Vacelet <manuel.vacelet-abecedaire@st.com>
 * @license    http://opensource.org/licenses/gpl-license.php GPL
 */
class WikiServiceAdminViews extends WikiViews {

  /**
   * WikiServiceAdminViews - Constructor
   */
  function WikiServiceAdminViews(&$controler, $id=0) {
    $GLOBALS['Language']->loadLanguageMsg('wiki/wiki');
    parent::WikiView($controler, $id);
    $this->html_params['title']  = $GLOBALS['Language']->getText('wiki_views_wkserviews', 'title', array(group_getname($this->gid)));
  }

  /**
   * displayEntryForm - private
   */
  function _displayEntryForm($act='', $id='', $name='', $page='', $desc='', $rank='') {
    $GLOBALS['Language']->loadLanguageMsg('wiki/wiki');
    print '<form name="wikiEntry" method="post" action="'.$this->wikiAdminLink.'&view=wikiDocuments">
             <input type="hidden" name="group_id" value="'.$this->gid.'" />
             <input type="hidden" name="action" value="'.$act.'" />
             <input type="hidden" name="id" value="'.$id.'" />
           <table>';

    print '<tr>
             <td>'.$GLOBALS['Language']->getText('wiki_views_wkserviews', 'entry_name').'</td>
             <td ><input type="text" name="name" value="'.$name.'" size="60" maxlength="255"/></td>
             <td>'.$GLOBALS['Language']->getText('wiki_views_wkserviews', 'entry_em').'</td>
           </tr>';

    $allPages =& WikiPage::getAllUserPages();
    $allPages[]='';

    $selectedPage = $page;
    $upageValue   = '';
    if(!in_array($page, $allPages)) {
        $selectedPage = '';
        $upageValue = $page;
    }
    print '<tr>
             <td>'.$GLOBALS['Language']->getText('wiki_views_wkserviews', 'wikipage').'</td>
             <td>
               '.html_build_select_box_from_array($allPages, 'page', $selectedPage, true).'<br />'.$GLOBALS['Language']->getText('wiki_views_wkserviews', 'givename').' <input type="text" name="upage" value="'.$upageValue.'" size="20" maxlength="255"/>
             </td>
             <td>'.$GLOBALS['Language']->getText('wiki_views_wkserviews', 'wikipage_em', array($this->wikiAdminLink)).'</td>
           </tr>';
    
    print '<tr>
             <td>'.$GLOBALS['Language']->getText('wiki_views_wkserviews', 'description').'</td>
             <td><textarea name="desc" rows="5" cols="60">'.$desc.'</textarea></td>
             <td>'.$GLOBALS['Language']->getText('wiki_views_wkserviews', 'description_em').'</td>
           </tr>';
    
    print '<tr>
             <td>'.$GLOBALS['Language']->getText('wiki_views_wkserviews', 'rank_screen').'</td>
             <td><input type="text" name="rank" value="'.$rank.'" size="3" maxlength="3"/></td>
             <td>'.$GLOBALS['Language']->getText('wiki_views_wkserviews', 'rank_screen_em').'</td>
           </tr>';

    print '<tr>
             <td colspan="3"><input type="submit" value="'.$GLOBALS['Language']->getText('wiki_views_wkserviews', 'act_'.$act).'" /></td>
           </tr>';

    print '</table>
           </form>';
  }

  /**
   * displayMenu - public
   */
  function displayMenu() {     
    switch(DEFAULT_LANGUAGE){	    
	case 'fr_FR': print '
		     <ul class="ServiceMenu">
		       <li><a href="/wiki/index.php?group_id='.$this->gid.'">Parcourir</a>&nbsp;|&nbsp;</li>
		       <li><a href="/wiki/admin/index.php?group_id='.$this->gid.'">Admin</a>&nbsp;|&nbsp;</li>
		       <li><a href="'.$this->wikiAdminLink.'&view=wikiDocuments">Documents Wiki</a>&nbsp;|&nbsp;</li>
		       <li><a href="'.$this->wikiAdminLink.'&view=wikiPages">Pages Wiki</a>&nbsp;|&nbsp;</li>
		       <li><a href="'.$this->wikiAdminLink.'&view=wikiAttachments">Fichiers joints</a>&nbsp;|&nbsp;</li>
		       <li><a href="'.$this->wikiAdminLink.'&view=wikiPerms">Permissions Wiki</a>&nbsp;|&nbsp;</li>
		       <li>'.help_button('WikiService.html',false,'Aide').'</li>
		     </ul>';
		     break;
	case 'en_US':
    default :     print '
		     <ul class="ServiceMenu">
		       <li><a href="/wiki/index.php?group_id='.$this->gid.'">View</a>&nbsp;|&nbsp;</li>
		       <li><a href="/wiki/admin/index.php?group_id='.$this->gid.'">Admin</a>&nbsp;|&nbsp;</li>
		       <li><a href="'.$this->wikiAdminLink.'&view=wikiDocuments">Wiki Documents</a>&nbsp;|&nbsp;</li>
		       <li><a href="'.$this->wikiAdminLink.'&view=wikiPages">Wiki Pages</a>&nbsp;|&nbsp;</li>
		       <li><a href="'.$this->wikiAdminLink.'&view=wikiAttachments">Wiki Attachments</a>&nbsp;|&nbsp;</li>
		       <li><a href="'.$this->wikiAdminLink.'&view=wikiPerms">Wiki Permissions</a>&nbsp;|&nbsp;</li>
		       <li>'.help_button('WikiService.html',false,'help').'</li>
		     </ul>';
    }
  }

  /**
   * main - public View
   *
   * Main and default view.
   */
  function main() {
    switch (DEFAULT_LANGUAGE){
	case 'fr_FR': printf("<h2>Wiki %s - Administration</h2><h3><a href=%s&view=wikiDocuments>G�rer les documents Wiki</a></h3><p>Cr�er, supprimer, modifier et donner des permissions sur des documents Wiki.</p>", $this->wikiname, $this->wikiAdminLink);
		     printf("<h3><a href=%s&view=wikiPages>G�rer les pages Wiki</a></h3><p>Parcourir et donner des permissions sur des pages Wiki.</p>", $this->wikiAdminLink);
		     printf("<h3><a href=%s&view=wikiAttachments>G�rer les fichiers joints</a></h3><p>Parcourir et d�finir les permissions des fichiers joints au Wiki</p>", $this->wikiAdminLink);
		     printf("<h3><a href=%s&view=wikiPerms>G�rer les permissions Wiki</a></h3><p>Donner des permissions sur tout le Wiki %s.</p>", $this->wikiAdminLink, $this->wikiname);
		     printf("<h3><a href=%s&pagename=AdministrationDePhpWiki>Administration du wiki</a></h3><p>Panneau d'administration de l'engin wiki. Plusieurs outils pour suppression , renommage et r�initialisation de pages.</p>", $this->wikiLink);
		     break;
	case 'en_US':	     
	default :     printf("<h2>Wiki  %s - Administration</h2><h3><a href= %s&view=wikiDocuments>Manage Wiki Documents</a></h3><p>Create, delete, modify and set specific permissions on Wiki Documents.</p>", $this->wikiname, $this->wikiAdminLink);
		     printf("<h3><a href=%s&view=wikiPages>Manage Wiki Pages</a></h3><p>Browse and set specific permissions on Wiki Pages.</p>", $this->wikiAdminLink); 	
		     printf("<h3><a href=%s&view=wikiAttachments>Manage Wiki Attachments</a></h3><p>Browse and set permissions on ressources attached on the Wiki.</p>", $this->wikiAdminLink);	
		     printf("<h3><a href=%s&view=wikiPerms>Set Wiki Permissions</a></h3><p>Set permissions on whole %s Wiki.</p>", $this->wikiAdminLink, $this->wikiname);
		     printf("<h3><a href=%s&pagename=PhpWikiAdministration>PhpWiki Administration</a></h3><p>Administration panel of the wiki engine. This propose a set of tools to delete and rename pages.</p>", $this->wikiLink);
		     break;
    
    }
    
 }


  /**
   * wikiDocuments - public view
   */
  function wikiDocuments() {
    $GLOBALS['Language']->loadLanguageMsg('wiki/wiki');
    echo $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wikidocs_title', array($this->wikiname));

    list($hideFlag, $hideUrl, $hideImg) = hide_url('wiki_admin_createdoc', $this->gid);
    $hurl='<a href="'.$this->wikiAdminLink.'&view=wikiDocuments&'.$hideUrl.'">'.$hideImg.'</a>';
    print $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wikidocs_create', array($hurl));
    if(!$hideFlag){
      $this->_createWikiDocument();
    }
    
    //    print "\n<hr/>\n";
    list($hideFlag, $hideUrl, $hideImg) = hide_url('wiki_admin_browsedoc', $this->gid);
    $hurl='<a href="'.$this->wikiAdminLink.'&view=wikiDocuments&'.$hideUrl.'">'.$hideImg.'</a>';
    print $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wikidocs_docs', array($hurl));
    if(!$hideFlag){
      $this->_browseWikiDocument();
    }
      
    print '<hr/><p><a href="'.$this->wikiAdminLink.'">'.$GLOBALS['Language']->getText('wiki_views_wkserviews', 'back_admin').'</a></p>'."\n";
  }

  /**
   * _createWikiDocument - private
   */
  function _createWikiDocument() {
    $GLOBALS['Language']->loadLanguageMsg('wiki/wiki');
    print $GLOBALS['Language']->getText('wiki_views_wkserviews', 'createwikidoc', array($this->wikiLink));
    $this->_displayEntryForm('create');
  }

  /**
   * _browseWikiDocument - private
   */
  function _browseWikiDocument() {
    $GLOBALS['Language']->loadLanguageMsg('wiki/wiki');

    $wei = WikiEntry::getEntryIterator($this->gid);
   
    print $GLOBALS['Language']->getText('wiki_views_wkserviews', 'browsedoc');
    
    print html_build_list_table_top(array($GLOBALS['Language']->getText('wiki_views_wkserviews', 'doc_name'),
                                          $GLOBALS['Language']->getText('wiki_views_wkserviews', 'doc_page'),
                                          $GLOBALS['Language']->getText('wiki_views_wkserviews', 'doc_rank'),
                                          $GLOBALS['Language']->getText('wiki_views_wkserviews', 'doc_delete')));
    $i=0;
    while($wei->valid()) {
        $we = $wei->current();

      print '<tr class="'.html_get_alt_row_color($i).'">';

      print '<td>
               <a href="'.$this->wikiAdminLink.'&view=updateWikiDocument&id='.$we->getId().'">'.$we->getName().'</a>
            </td>';

      print '<td>';
      print $we->getPage();
      print ' - ';
      print '<a href="'.$this->wikiAdminLink.'&view=docPerms&id='.$we->wikiPage->getId().'">';
      $status = $GLOBALS['Language']->getText('wiki_views_wkserviews', 'define_perms');
      if($we->wikiPage->permissionExist()) {
        $status = $GLOBALS['Language']->getText('wiki_views_wkserviews', 'edit_perms');
      }
      print '['.$status.']';
      print '</a>';
      print '</td>';

      print '<td align="center">'.$we->getRank().'</td>';

      print '<td align="center">';

      $alt = $GLOBALS['Language']->getText('wiki_views_wkserviews', 'deletedoc', array($we->getName()));
      print html_trash_link($this->wikiAdminLink.'&view=wikiDocuments&action=delete&id='.$we->getId(),
                            $GLOBALS['Language']->getText('common_mvc_view','warn',$alt),
                            $alt);
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
  function updateWikiDocument() {
    $GLOBALS['Language']->loadLanguageMsg('wiki/wiki');
    print $GLOBALS['Language']->getText('wiki_views_wkserviews', 'updatedoc', array($this->wikiname));

    $we = new WikiEntry($_REQUEST['id']);
    $this->_displayEntryForm('update',
			    $we->getId(),
			    $we->getName(),
			    $we->getPage(),
			    $we->getDesc(),
			    $we->getRank());
    print '<p><a href="'.$this->wikiAdminLink.'&view=wikiDocuments">'.$GLOBALS['Language']->getText('global', 'back').'</a></p>'."\n";
  }

  /**
   * This function is a "false" document permission view. Actually, 
   * it set permission on a page. This function only exist to make an
   * auto return on wikiDocuments view after permission settings.
   * 
   *
   * pagePerms - public View
   */
   function docPerms() {
    $GLOBALS['Language']->loadLanguageMsg('wiki/wiki');
     $postUrl = '/wiki/admin/index.php?group_id='.$this->gid.'&view=wikiDocuments&action=setWikiPagePerms';
     $this->_pagePerms($postUrl);    
     print '<p><a href="'.$this->wikiAdminLink.'&view=wikiPages"'.$GLOBALS['Language']->getText('global', 'back').'</a></p>'."\n";
  }


  /**
   * pagePerms - public View
   */
   function pagePerms() {
     $GLOBALS['Language']->loadLanguageMsg('wiki/wiki');
     $postUrl = '/wiki/admin/index.php?group_id='.$this->gid.'&view=wikiPages&action=setWikiPagePerms';
     $this->_pagePerms($postUrl);    
     print '<p><a href="'.$this->wikiAdminLink.'&view=wikiPages">'.$GLOBALS['Language']->getText('global', 'back').'</a></p>'."\n";
  }

  /**
   * wikiPages - public View
   */
  function wikiPages() {
    $GLOBALS['Language']->loadLanguageMsg('wiki/wiki');
    echo $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wkpage_title', array($this->wikiname));
    
    list($hideFlag, $hideUrl, $hideImg) = hide_url('wiki_project_pages', $this->gid);
    $hurl='<a href="'.$this->wikiAdminLink.'&view=wikiPages&'.$hideUrl.'">'.$hideImg.'</a>';
    echo $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wkpage_project', array($hurl));
    if(!$hideFlag){
      print $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wkpage_pj_all');
      $allUserPages =& WikiPage::getAllUserPages();
      $this->_browsePages($allUserPages);
    }

    list($hideFlag, $hideUrl, $hideImg) = hide_url('wiki_empty_pages', $this->gid);
    $hurl='<a href="'.$this->wikiAdminLink.'&view=wikiPages&'.$hideUrl.'">'.$hideImg.'</a>';
    echo $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wkpage_empty', array($hurl));
    if(!$hideFlag){
      print $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wkpage_emp_all');
      $wpw = new WikiPageWrapper($this->gid);
      $allEmptyPages =& $wpw->getProjectEmptyLinks();
      $this->_browsePages($allEmptyPages);
    }

    list($hideFlag, $hideUrl, $hideImg) = hide_url('wiki_admin_pages', $this->gid);
    $hurl='<a href="'.$this->wikiAdminLink.'&view=wikiPages&'.$hideUrl.'">'.$hideImg.'</a>';
    echo $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wkpage_admin', array($hurl));
    if(!$hideFlag){
      print $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wkpage_adm_all');
      $allAdminPages =& WikiPage::getAllAdminPages();
      $this->_browsePages($allAdminPages);
    }

    list($hideFlag, $hideUrl, $hideImg) = hide_url('wiki_internal_pages', $this->gid, true);
    $hurl='<a href="'.$this->wikiAdminLink.'&view=wikiPages&'.$hideUrl.'">'.$hideImg.'</a>';
    echo $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wkpage_intern', array($hurl));
    if(!$hideFlag){
      print $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wkpage_int_all');
      $allInternalsPages =& WikiPage::getAllInternalPages();
      $this->_browsePages($allInternalsPages);
    }
    
    print '<hr/><p><a href="'.$this->wikiAdminLink.'">'.$GLOBALS['Language']->getText('wiki_views_wkserviews', 'back_admin').'</a></p>'."\n";
  }


  /**
   * browsePages - private
   */
  function _browsePages(&$pageList) {
    $GLOBALS['Language']->loadLanguageMsg('wiki/wiki');
    print html_build_list_table_top(array('Page', 'Permissions'));

    sort($pageList);
    $i=0;
    foreach($pageList as $pagename) {
      print '
            <tr class="'.html_get_alt_row_color($i).'">
            ';

      print '<td><a href="'.$this->wikiLink.'&pagename='.urlencode($pagename).'">'.$pagename.'</a></td>';

      $page   = new WikiPage($this->gid, $pagename);
      $status = $GLOBALS['Language']->getText('wiki_views_wkserviews', 'define_perms');
      if (permission_exist('WIKIPAGE_READ',$page->getId())) {
	$status = $GLOBALS['Language']->getText('wiki_views_wkserviews', 'edit_perms');
      }
      print '<td align="center">';
      print '<a href="'.$this->wikiAdminLink.'&view=pagePerms&id='.$page->getId().'">['.$status.']</a>';
      print '</td>';

      print '
            </tr>
            ';
      
      $i++;
    }
    print '</TABLE>';
  }

  /**
   * wikiPerms - public View
   */
  function wikiPerms() {
    $GLOBALS['Language']->loadLanguageMsg('wiki/wiki');
    echo $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wikiperm', array($this->wikiname, $this->wikiname));
    $postUrl = '/wiki/admin/index.php?group_id='.$this->gid.'&action=setWikiPerms';
    permission_display_selection_form("WIKI_READ", $this->gid, $this->gid, $postUrl);

    print '<hr/><p><a href="'.$this->wikiAdminLink.'">'.$GLOBALS['Language']->getText('wiki_views_wkserviews', 'back_admin').'</a></p>'."\n";
  }
  
    /**
     * @access public
     */
    function wikiAttachments() {
        $GLOBALS['Language']->loadLanguageMsg('wiki/wiki');
        echo $GLOBALS['Language']->getText('wiki_views_wkserviews', 'attachment_title', array($this->wikiname));
   
        print html_build_list_table_top(array($GLOBALS['Language']->getText('wiki_views_wkserviews', 'attachment_name'),
                                              $GLOBALS['Language']->getText('wiki_views_wkserviews', 'attachment_revisions'), 
                                              $GLOBALS['Language']->getText('wiki_views_wkserviews', 'attachment_permissions')
                                              /*, 'Delete ?'*/));

        $wai =& WikiAttachment::getAttachmentIterator($this->gid);
        $wai->rewind();
        while($wai->valid()) {
            $wa =& $wai->current();

            print '<tr>';

            $filename = basename($wa->getFilename());
            $id = $wa->getId();

            print '<td><a href="'.$this->wikiAdminLink.'&view=browseAttachment&id='.$id.'">'.$filename.'</a></td>';
            print '<td align="center">'.$wa->count().'</td>';

            $status=$GLOBALS['Language']->getText('wiki_views_wkserviews', 'define_perms');
            if($wa->permissionExist()) {
                $status=$GLOBALS['Language']->getText('wiki_views_wkserviews', 'edit_perms');
            }
            print '<td align="center">';
            print '<a href="'.$this->wikiAdminLink.'&view=attachmentPerms&id='.$id.'">['.$status.']</a>';
            print '</td>';


            //print '<td align="center">';
            //      print $this->getTrashLink($this->wikiAdminLink.'&view=wikiAttachments&action=delAttach&id='
            //			, 'delete "'.$filename.'" attachment');
            // print 'n/a';
            //print '</td>';
            print '</tr>';

            $wai->next();
        }
    
        print '</table>';
        print '<hr/><p><a href="'.$this->wikiAdminLink.'">'.$GLOBALS['Language']->getText('wiki_views_wkserviews', 'back_admin').'</a></p>'."\n";
    }
  

    function attachmentPerms() {
        $GLOBALS['Language']->loadLanguageMsg('wiki/wiki');
        $attachmentId = $_GET['id'];
    
        $wa = new WikiAttachment($this->gid);
        $wa->initWithId($attachmentId);

        echo $GLOBALS['Language']->getText('wiki_views_wkserviews', 'perm_attachment_title', array($this->wikiname));
   
        echo $GLOBALS['Language']->getText('wiki_views_wkserviews', 'wiki_attachment_perm', array($wa->getFilename()));
    
        $postUrl = $this->wikiAdminLink.'&view=wikiAttachments&action=setWikiAttachmentPerms';
        permission_display_selection_form("WIKIATTACHMENT_READ", $wa->getId(), $this->gid, $postUrl);

        print '<hr/><p><a href="'.$this->wikiAdminLink.'&view=wikiAttachments">'.$GLOBALS['Language']->getText('wiki_views_wkserviews', 'back_admin').'</a></p>'."\n";
    }

    /**
     * @access public
     */
    function browseAttachment() {
        $GLOBALS['Language']->loadLanguageMsg('wiki/wiki');
        $attachmentId = (int) $_GET['id'];
    
        $wa = new WikiAttachment($this->gid);
        $wa->initWithId($attachmentId);
              
        echo $GLOBALS['Language']->getText('wiki_views_wkserviews', 'browse_attachment_title', array($this->wikiname,$wa->getFilename()));

        // if($wari->exist()) {      
        print html_build_list_table_top(array($GLOBALS['Language']->getText('wiki_views_wkserviews', 'attachment_revision'),
                                              $GLOBALS['Language']->getText('wiki_views_wkserviews', 'attachment_date'),
                                              $GLOBALS['Language']->getText('wiki_views_wkserviews', 'attachment_author'),
                                              $GLOBALS['Language']->getText('wiki_views_wkserviews', 'attachment_size')));
        $wari =& WikiAttachmentRevision::getRevisionIterator($this->gid, $attachmentId);
        $wari->rewind();
        while($wari->valid()) {
            $war =& $wari->current();

            print '
             <tr>
	       <td><a href="/wiki/uploads/'.$this->gid.'/'.$wa->getFilename().'/'.($war->getRevision()+1).'">'.($war->getRevision()+1).'</a></td>
	       <td>'.strftime("%e %b %Y %H:%M", $war->getDate()).'</td>
               <td><a href="/users/'.user_getname($war->getOwnerId()).'/">'.user_getname($war->getOwnerId()).'</td>
	       <td>'.$war->getSize().'</td>
	     </tr>';

            $wari->next();
        }
    
        print '</table>';
        // }
        // else {
        //   print 'not found';
        // }
        print '<hr/><p><a href="'.$this->wikiAdminLink.'&view=wikiAttachments">'.$GLOBALS['Language']->getText('wiki_views_wkserviews', 'back_admin').'</a></p>'."\n";
    }

    /**
     *
     */
    function install() {
        $wpw = new WikiPageWrapper($this->gid);
        $wpw->install();
    }
    
    function upgrade() {
        $wpw = new WikiPageWrapper($this->gid);
          
        $nbGroupPending = null;
        $nextId = $wpw->getNextGroupWithWiki($this->gid, $nbGroupPending);
       
        $html .= 'Nb project to go: '.$nbGroupPending.'<br>';
         
        $url  = '/wiki/admin/index.php?group_id='.$nextId.'&view=upgrade';
        $href = '<a href="'.$url.'">'.$nextId.'</a>';
        $html .= 'Next project: '.$href.'<br>';
          
        print $html;

        $wpw->upgrade();
    }  
         
}
?>