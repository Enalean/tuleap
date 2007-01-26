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
require_once(dirname(__FILE__).'/WikiViews.class.php');
require_once(dirname(__FILE__).'/../lib/WikiPage.class.php');
require_once(dirname(__FILE__).'/../lib/WikiEntry.class.php');

/**
 * 
 * @package   WikiService
 * @copyright STMicroelectronics, 2005
 * @author    Manuel Vacelet <manuel.vacelet-abecedaire@st.com>
 * @license   http://opensource.org/licenses/gpl-license.php GPL
 */
class WikiServiceViews extends WikiViews {

  /**
   * WikiServiceViews - Constructor
   */
  function WikiServiceViews(&$controler, $id=0, $view=null) {
    $GLOBALS['Language']->loadLanguageMsg('wiki/wiki');
    parent::WikiView($controler, $id, $view);
    if(!is_null($_REQUEST['pagename'])) {
        $this->html_params['title']  = $GLOBALS['Language']->getText('wiki_views_wikiserviceviews',
                                                          'wiki_page_title',
                                                          array($_REQUEST['pagename'],
                                                                group_getname($this->gid)));
    }
    else {
        $this->html_params['title']  = $GLOBALS['Language']->getText('wiki_views_wikiserviceviews',
                                                          'wiki_title',
                                                          array(group_getname($this->gid)));
    }
    $GLOBALS['wiki_view'] =& $this;
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
  function browse() {
    $GLOBALS['Language']->loadLanguageMsg('wiki/wiki');
    list($hideFlag, $hideUrl, $hideImg) = hide_url('wiki_browse_documents', $this->gid);
    $hurl='<a href="'.$this->wikiLink.'&'.$hideUrl.'">'.$hideImg.'</a>';
    print $GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'wiki_subtit_docu', array($hurl));
    if(!$hideFlag) {
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
  function browsePages() {    
    $GLOBALS['Language']->loadLanguageMsg('wiki/wiki');
    list($hideFlag, $hideUrl, $hideImg) = hide_url('wiki_browse_pages', $this->gid);
    $hurl='<a href="'.$this->wikiLink.'&view=browsePages&'.$hideUrl.'">'.$hideImg.'</a>';
    print $GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'wiki_subtit_pages', array($hurl));
    if(!$hideFlag) {
      $this->_browseProjectWikiPages();
    }

    list($hideFlag, $hideUrl, $hideImg) = hide_url('wiki_browse_empty_pages', $this->gid);
    $hurl='<a href="'.$this->wikiLink.'&view=browsePages&'.$hideUrl.'">'.$hideImg.'</a>';
    print $GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'wiki_subtit_empty', array($hurl));
    if(!$hideFlag) {
      $this->_browseEmptyWikiPages();
    }

    list($hideFlag, $hideUrl, $hideImg) = hide_url('wiki_create_new_page', $this->gid);
    $hurl='<a href="'.$this->wikiLink.'&view=browsePages&'.$hideUrl.'">'.$hideImg.'</a>';
    print $GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'wiki_subtit_create', array($hurl));
    if(!$hideFlag) {
      $this->_newPageForm($this->wikiLink.'&view=browsePages');
    }

  }

 /**
   *
   */
  function _browseWikiDocuments() {
    $GLOBALS['Language']->loadLanguageMsg('wiki/wiki');

    $wei =& WikiEntry::getEntryIterator($this->gid);

    print '<ul class="WikiEntries">';
    while($wei->valid()) {
        $we = $wei->current();

      $href = $this->_buildPageLink($we->wikiPage, $we->getName());
      if(!empty($href)) {
          print $GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'wikientries', array($href, $we->getDesc()));
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
  function _browseProjectWikiPages() {
    $allPages =& WikiPage::getAllUserPages();
    $this->_browsePages($allPages);
  }

  /**
   * _browseProjectPages - private
   *
   * Display empty pages.
   */
  function _browseEmptyWikiPages() {
    $wpw = new WikiPageWrapper($this->gid);
    $allPages =& $wpw->getProjectEmptyLinks();
    $this->_browsePages($allPages);
  }

  /**
   * _browsePages - private
   *
   * @param  string[] $pagelist List of page names.
   */
  function _browsePages(&$pageList) {
    print '<ul class="WikiEntries">';
    foreach($pageList as $pagename) {
      $wp = new WikiPage($this->gid, $pagename);
      $href = $this->_buildPageLink($wp);
      if(!empty($href)) {
	print '<li>'.$href.'</li>';
      }
    }
    print "</ul>";
  }

  /**
   * _newPageForm - private
   *
   * @param  string $addr Form action adress
   */
  function _newPageForm($addr='') {
    $GLOBALS['Language']->loadLanguageMsg('wiki/wiki');
    print '
    <form name="newPage" method="post" action="'.$addr.'">
      <input type="hidden" name="action" value="add_temp_page" />
      <input type="hidden" name="group_id" value="'.$this->gid.'" />'.$GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'pagename').' <input type="text" name="name" value="" size="20" maxsize="255" />
      <input type="submit" value="'.$GLOBALS['Language']->getText('global', 'btn_create').'">
    </form>';
   }

  /**
   * _buildPageLink - private
   *
   * @param  WikiPage $wikiPage
   * @param  string   $title
   * @return string   $href
   */
  function _buildPageLink(&$wikiPage, $title=null) {
    $href='';
    // Check permission
    if($wikiPage->isAutorized(user_getid())) {

      $pagename = $wikiPage->getPagename();

      // Build page link
      if(empty($title))
	$title = $pagename;
 
      $link='/wiki/index.php?group_id='.$this->gid.'&pagename='.urlencode($pagename);
      
      
      // Display title as emphasis if corresponding page does't exist.
      if($wikiPage->isEmpty()) {
	$title = '<em>'.$title.'</em>';
	$link .= '&action=edit';
      }
      
      // Build Lock image if a permission is set on the corresponding page
      if($wikiPage->permissionExist()) {
	$permLink = $this->wikiLink.'&view=pagePerms&id='.$wikiPage->getId();
	$title = $title.'<img src="'.util_get_image_theme("ic/lock.png").'" border="0" alt="Lock" />';
      }

      $href='<a href="'.$link.'">'.$title.'</a>';
    }
    return $href;
  }

  /**
   * header - public
   */
  function header() {
    if(!browser_is_netscape4()) {
      $this->html_params['stylesheet'][] = '/wiki/themes/CodeX/phpwiki.css';
    }
    parent::header();
  }

  /**
   * displayMenu - public
   */
  function displayMenu() {
    $GLOBALS['Language']->loadLanguageMsg('wiki/wiki');
    
    print '
    <table class="ServiceMenu">
      <tr>
        <td>';
    switch(DEFAULT_LANGUAGE){
	    case 'en_US': $attatch_page = 'UpLoad';
			 $attatch_menu = 'Attach a file';   
			 $preferences_page = 'UserPreferences';
			 $preferences_menu = 'Preferences';
			 $help_menu = 'Help';
			 break;
	    case 'fr_FR': $attatch_page = "D�poserUnFichier";
			 $attatch_menu = "Joindre un fichier";
			 $preferences_page = "Pr�f�rencesUtilisateurs";   
			 $preferences_menu = "Pr�f�rences";
			 $help_menu = 'Aide';
			 break;   
	    default :     $attatch_page = 'UpLoad';
			 $attatch_menu = 'Attach a file';   
			 $preferences_page = 'UserPreferences';
			 $preferences_menu = 'Preferences';
 			 $help_menu = 'Help';
    }	
    print '
    <ul class="ServiceMenu">
      <li><a href="'.$this->wikiLink.'">' . $GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'menudocuments') . '</a>&nbsp;|&nbsp;</li>
      <li><a href="'.$this->wikiLink.'&view=browsePages">'.$GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'menupages').'</a>&nbsp;|&nbsp;</li>
      <li><a href="javascript:help_window(\''.$this->wikiLink.'&pagename='. $attatch_page .'&pv=1\')">'.$attatch_menu.'</a>&nbsp;|&nbsp;</li>
      <li><a href="'.$this->wikiLink.'&pagename='. $preferences_page .'">'.$preferences_menu.'</a>&nbsp;|&nbsp;</li>';
    if(user_ismember($this->gid, 'W2')) {
        print '<li><a href="'.$this->wikiAdminLink.'">'.$GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'menuadmin').'</a>&nbsp;|&nbsp;</li>';
    }
    
    print '<li>'.help_button('WikiService.html', false, $help_menu).'</li>
   </ul>';
    
  print '
  </td>
  <td align="right">';
  
  
  if(user_ismember($this->gid, 'W2')) {
      $wiki = new Wiki($this->gid);
      $permInfo="";
      if('wiki' == $this->view) {
          // User is browsing a wiki page
          $wp = new WikiPage($this->gid, $_REQUEST['pagename']);
          
          $permLink = $this->wikiAdminLink.'&view=pagePerms&id='.$wp->getId();
          if($wp->permissionExist()) {
              $permInfo =  '<a href="'.$permLink.'"> '.'<img src="'.util_get_image_theme("ic/lock.png").'" border="0" alt="'.$GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'lock_alt').'" title="'.$GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'lock_title_spec').'"/></a>';
          }
      }
      if ($wiki->permissionExist()) {
          $permInfo .=  '<a href="/wiki/admin/index.php?group_id='.$this->gid.'&view=wikiPerms"> '.'<img src="'.util_get_image_theme("ic/lock.png").'" border="0" alt="'.$GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'lock_alt').'" title="'.$GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'lock_title_set').'"/>'.'</a>';
      }
      if ($permInfo) print $permInfo;

  }



  print '
      (<a href="'.$_SERVER['REQUEST_URI'].'&pv=1" title="'.$GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'lighter_display').'">
      <img src="'.util_get_image_theme("msg.png").'" border="0">&nbsp;'.
      $GLOBALS['Language']->getText('global','printer_version').'</A> ) 
      </li>';
    

 
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
  function pagePerms() {
     $GLOBALS['Language']->loadLanguageMsg('wiki/wiki');
     $postUrl = '/wiki/index.php?group_id='.$this->gid.'&action=setWikiPagePerms';     
     $this->_pagePerms($postUrl);
     print '<p><a href="'.$this->wikiLink.'">'.$GLOBALS['Language']->getText('global', 'back').'</a></p>'."\n";
  }

  /**
   * View display a Wiki Page.
   *
   * @access public
   */
  function wiki() {
    $wp = new WikiPage($this->gid, $_REQUEST['pagename']);

    $wp->log(user_getid());

    $lite = false;
    $full_screen = false;
    if(isset($_GET['pv']) && ( $_GET['pv'] == 1)) {
      $lite = true;
    }
    if(isset($_GET['pv']) && ( $_GET['pv'] == 2)) {
      $full_screen = true;
    }
    $wp->render($lite, $full_screen);
  }

  /**
   * display - public
   * @access public
   */
  function display($view='') {
      $GLOBALS['type_of_search'] = 'wiki';

    switch($view) {
    case 'empty':
      $this->wiki();
      break;
      
    case 'doinstall':
        if(!empty($view)) $this->$view();
        break;

    case 'browse':
    default:
      $this->header();
      if(!empty($view)) $this->$view();
      $this->footer();
    }
  }


  /**
   * install: ask for confirmation and choose language
   */
  function install() {
    $GLOBALS['Language']->loadLanguageMsg('wiki/wiki');
    echo $GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 
                            'install_intro', 
                            array($GLOBALS['Language']->getText('global','btn_create')));
    // Display creation form
    echo '<form name="WikiCreation" method="post" action="'.$this->wikiLink.'">
             <input type="hidden" name="group_id" value="'.$this->gid.'" />
             <input type="hidden" name="view" value="doinstall" />'.$GLOBALS['Language']->getText('wiki_views_wikiserviceviews', 'wiki_language').' ';
echo html_get_language_popup($GLOBALS['Language'],'language_id',$GLOBALS['Language']->getLanguageId());
echo '<input type="submit" value="'.$GLOBALS['Language']->getText('global','btn_create').'">
</form>';
  }


  /**
   * install
   */
  function doinstall() {
      global $LANG;
      global $language_id;
      $GLOBALS['Language']->loadLanguageMsg('wiki/wiki');
      $language_id=$_REQUEST['language_id'];
      if (!$language_id) $language_id=1; // default is en_US
      // Initial Wiki document is now created within phpWiki main()
      // Make sure phpWiki instantiates the right pages corresponding the the given language
      define('DEFAULT_LANGUAGE',language_id_to_language_code($language_id));
      $LANG=language_id_to_language_code($language_id);

      $wpw = new WikiPageWrapper($this->gid);
      $wpw->install();
  }

}
?>