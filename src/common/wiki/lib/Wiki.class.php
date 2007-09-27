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
require_once('www/project/admin/permissions.php');
require_once('WikiPage.class.php');
/**
 * Manipulation of Wiki service.
 * 
 * This class is a part of the Model of Wiki Service it aims to be the
 * interface between data corresponding to a Wiki Service (instance of
 * PhpWiki for CodeX) and CodeX application
 *
 * @package   WikiService
 * @copyright STMicroelectronics, 2005
 * @author    Manuel Vacelet <manuel.vacelet-abecedaire@st.com>
 * @license   http://opensource.org/licenses/gpl-license.php GPL
 */
class Wiki {
  /* private int */ var $gid;
  /* private int */ var $language_id;
    /* private int */ var $exist;

  /**
   * WikiSericeModel - Constructor
   *
   * @access public
   * @param  int $id Project identifier
   */
  function Wiki($id=0) {
    $this->gid = (int) $id;
    $this->exist = null;
  }

  /**
   *
   * @return boolean Return if a permission is set on this Wiki
   */
  function permissionExist() {
    return permission_exist('WIKI_READ', $this->gid);
  }

  /**
   * Check if user can access to whole wiki
   *
   * checkPermissions - Public
   * @param  int     User identifier
   * @return boolean Is the given user allowed to access to the Wiki
   */
  function isAutorized($uid) {
    $autorized = permission_is_authorized('WIKI_READ', $this->gid, $uid, $this->gid);
    return $autorized;
  }

  /**
   * Set access permissions.
   *
   * @param  string[] $groups List of groups allowed to access to the Wiki
   * @return boolean  Modification status
   */
  function setPermissions($groups) {
    global $feedback;

    list ($ret, $feedback) = permission_process_selection_form($this->gid, 
							       'WIKI_READ', 
							       $this->gid, 
							       $groups);
    return $ret;
  }

 
  /**
   * Reset access permissions.
   *
   * @return boolean  Modification status
   */
  function resetPermissions() {
    return permission_clear_all($this->gid, 
                                'WIKI_READ', 
                                $this->gid);
  }

 
  /**
   * Check WikiEntry existance for given project.
   * @return boolean
   */
  function exist() {
      if($this->exist === null) {
          $res = db_query('SELECT count(*) AS nb FROM wiki_page'
                          .' WHERE group_id='.$this->gid);

          $this->exist = (db_result($res, 0, 'nb') > 0);
      }
      return $this->exist;
  }

  /**
   * Get number of wiki pages.
   * @return number of pages (0 if wiki is empty)
   */
  function getPageCount() {
    $res = db_query(' SELECT count(*) as count'
		    .' FROM wiki_page, wiki_nonempty'
		    .' WHERE wiki_page.group_id="'.$this->gid.'"'
		    .' AND wiki_nonempty.id=wiki_page.id');
    
    if(db_numrows($res) > 0) 
      return db_result($res,0,'count');
    else
      return 0;
  }

  
  /**
   * Get number of project wiki pages.
   * @return number of project pages (0 if wiki is empty)
   */
  function getProjectPageCount() {
    $res = db_query(' SELECT count(*) as count'
		    .' FROM wiki_page, wiki_nonempty'
		    .' WHERE wiki_page.group_id="'.$this->gid.'"'
		    .' AND wiki_nonempty.id=wiki_page.id'
            .' AND wiki_page.pagename NOT IN ("'.implode('","', WikiPage::getDefaultPages()).'",
                                              "'.implode('","', WikiPage::getAdminPages()).'")');
    
    if(db_numrows($res) > 0) 
      return db_result($res,0,'count');
    else
      return 0;
  }

  
  /** 
   * Get wiki language (set at creation time)
   * return 0 if no wiki document exist
   */
  function getLanguage_id() {
      // The language of the wiki is the language of all its wiki documents.
      if (!$this->language_id) {
          // We only support one language for all the wiki documents of a project.
          $wei =& WikiEntry::getEntryIterator($this->gid);
          if ($wei->valid()) {
              $we =& $wei->current(); // get first element  
              $this->language_id = $we->getLanguage_id();
          }
      }
      return $this->language_id;
  }


  /**
   * Experimental
   */

  function dropLink($id) {
    $res = db_query('  DELETE FROM wiki_link'
		    .' WHERE linkfrom='.$id
		    .' OR linkto='.$id);

    if(db_affected_rows($res) === 1)
      return true;

    
  }

  function dropNonEmpty($id) {
    $res = db_query('  DELETE FROM wiki_nonempty'
		    .' WHERE id='.$id);

   
  }

  function dropRecent($id) {
    $res = db_query('  DELETE FROM wiki_recent'
		    .' WHERE id='.$id);

    
  }

  function dropVersion($id) {
    $res = db_query('  DELETE FROM wiki_version'
		    .' WHERE id='.$id);

   
    
  }

  function dropPage($id) {
    $res = db_query('  DELETE FROM wiki_page'
		    .' WHERE id='.$id);
  }

  function drop() {
    //TODO: Drop entries


    //
    // PhpWiki
    //
    $res = db_query('  SELECT id FROM wiki_page'
		    .' WHERE group_id='.$this->gid);
    
    while($row = db_fetch_array($res)) {
      $pid = $row['id'];

      // Link
      $this->dropLink($pid);

      // Non empty
      $this->dropNonEmpty($pid);

      // Recent
      $this->dropRecent($pid);

      // Version
      $this->dropVersion($pid);

      // Page
      $this->dropPage($pid);
    }
  }
}
?>