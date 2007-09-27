<?php
/* 
 * Copyright 2005, 2006 STMicroelectronics
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

require_once('HTMLCleaner.class.php');
require_once('WikiPage.class.php');
require_once('common/collection/ArrayIterator.class.php');

/**
 *
 * @package   WikiService
 * @copyright STMicroelectronics, 2005
 * @author    Manuel Vacelet <manuel.vacelet-abecedaire@st.com>
 * @license   http://opensource.org/licenses/gpl-license.php GPL
 */
class WikiEntry {
  /* private int(11) */     var $id;
  /* private int(11) */     var $gid;
  /* private int(11) */     var $rank;
  /* private int(11) */     var $language_id;
  /* private string(255) */ var $name;
  /* private string(255) */ var $page;
  /* private string(255) */ var $desc;
  /* private WikiPage */    var $wikiPage;

  /**
   * Constructor
   */
  function WikiEntry($id=null) {
    if(empty($id)) {
      $this->id   = 0;
      $this->gid  = 0;
      $this->rank = 0;
      $this->language_id = 1;
      $this->name = '';
      $this->page = '';
      $this->desc = '';
      $this->wikiPage = null;
    }
    else {
      $this->setId($id);
      $this->_setFromDb();
    }
  }

  /**
   * Set
   */

  function setId($id) {
    $this->id = (int) $id;
  }

  function setGid($gid) {
    $this->gid = (int) $gid;
  }

  function setRank($rank) {
    $this->rank = (int) $rank;
  }

  function setLanguage_id($language_id) {
    $this->language_id = (int) $language_id;
  }

  function setName($name) {
    $this->name = HTMLCleaner::clean($name);
  }

  function setPage($page) {
    $page = str_replace('&', '', $page); 
    $page = str_replace('&amp;', '', $page); 
    $this->page = HTMLCleaner::clean($page, HTMLCLEANER_FULL);
  }

  function setDesc($desc) {
    $this->desc = HTMLCleaner::clean($desc);
  }

  function setFromRow($row) {
    $this->id   = $row['id'];
    $this->gid  = $row['group_id'];
    $this->rank = $row['rank'];
    $this->language_id = $row['language_id'];
    $this->name = $row['wiki_name'];
    $this->page = $row['wiki_link'];
    $this->desc = $row['description'];

    $this->wikiPage = new WikiPage($this->gid, $this->page);
  }

  function _setFromDb() {
    $res = db_query(' SELECT * FROM wiki_group_list'.
	     ' WHERE id='.$this->id);
    $row = db_fetch_array($res);
    $this->setFromRow($row);
  }

  
  /**
   * Get
   */

  function getId() {
    return $this->id;
  }

  function getGid() {
    return $this->gid;
  }

  function getRank() {
    return $this->rank;
  }

  function getLanguage_id() {
    return $this->language_id;
  }

  function getName() {
    return stripslashes($this->name);
  }

  function getPage() {
    return $this->page;
  }

  function getDesc() {
    return stripslashes($this->desc);
  }

    /**
     * Return an iterator on WikiEntries
     */
    function getEntryIterator($gid=null) {
        if($gid !== null) {
            $gid = (int) $gid;
        }
        else {
            $gid = $this->gid;
        }

        //@todo: transfer to a DAO
        $qry = ' SELECT * FROM wiki_group_list'
            .' WHERE group_id='.$gid
            .' ORDER BY rank';
        
        $res = db_query($qry);

        $weArray = array();
        while($row = db_fetch_array($res)) {
            $we =& new WikiEntry();
            $we->setFromRow($row);
            $weArray[] =& $we;
            unset($we);
        }

        return new ArrayIterator($weArray);
    }

  /**
   * Data handle
   */
  function add() {
    $res = db_query(' INSERT INTO wiki_group_list SET'.
		    ' group_id='.$this->gid.','.
		    ' rank='.$this->rank.','.
		    ' language_id='.$this->language_id.','.
		    ' wiki_name="'.mysql_real_escape_string($this->name).'",'.
		    ' wiki_link="'.mysql_real_escape_string($this->page).'",'.
		    ' description="'.mysql_real_escape_string($this->desc).'"');
    
    if($res === false) {
        $GLOBALS['Language']->loadLanguageMsg('wiki/wiki');
      trigger_error($GLOBALS['Language']->getText('wiki_lib_wikientry',
				       'insert_err',
				       db_error()), 
		    E_USER_ERROR);
      return false;
    }
    else {
      return true;
    }
  }

  function del() {
    $res = db_query(' DELETE FROM wiki_group_list'.
		    ' WHERE id='.$this->id.
		    ' AND group_id='.$this->gid);

    if($res === false) {
        $GLOBALS['Language']->loadLanguageMsg('wiki/wiki');
      trigger_error($GLOBALS['Language']->getText('wiki_lib_wikientry',
				       'delete_err', 
				       db_error()), 
		    E_USER_ERROR);
          return false;
    }
    else {
      return true;
    }
  }

  function update() {
      global $feedback;
      $sql = ' UPDATE wiki_group_list SET'
          . ' group_id='.$this->gid.','
          . ' rank='.$this->rank.','
          . ' language_id='.$this->language_id.','
          . ' wiki_name="'.mysql_real_escape_string($this->name).'",'
          . ' wiki_link="'.mysql_real_escape_string($this->page).'",'
          . ' description="'.mysql_real_escape_string($this->desc).'"'
          . ' WHERE id='.$this->id;
      
      $res = db_query($sql);
      $err = db_error();
      
      if($res === false) {
          $GLOBALS['Language']->loadLanguageMsg('wiki/wiki');
	trigger_error($GLOBALS['Language']->getText('wiki_lib_wikientry',
					 'update_err',
					 db_error()),
		      E_USER_ERROR);
	return false;
      }
      else {
	if(db_affected_rows() === 0) {
        $GLOBALS['Language']->loadLanguageMsg('wiki/wiki');
          $feedback .= $GLOBALS['Language']->getText('wiki_lib_wikientry',
					  'no_update',
					  $this->name);
	}
	return true;
      }
  }
}

?>