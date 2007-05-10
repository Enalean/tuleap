<?php
/* 
 * Copyright 2007, STMicroelectronics
 *
 * Originally written by Sabri LABBENE <sabri.labbene@st.com>
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


/**
 * Creates a clone of a Wiki.
 * 
 * This class is a part of the Model of Wiki Service it aims to be the
 * interface between data corresponding to a Wiki Service (instance of
 * PhpWiki for CodeX) and CodeX application
 *
 * @package   WikiService
 * @copyright STMicroelectronics, 2007
 * @author    Sabri LABBENE <sabri.labbene@st.com>
 * @license   http://opensource.org/licenses/gpl-license.php GPL
 */
class WikiCloner {
    /*private int */   var $tmpl_wiki_exist;


 /**
   * Constructor
   *
   *  @access public
   *  @param Template project identifier
   *  @param New project identifier
   *
   */
  function WikiCloner($template_id=0, $group_id=0) {
      
    $this->template_id = (int) $template_id;
    $this->group_id = (int) $group_id;
    $this->tmpl_wiki_exist = null;

  }
  
 /**
   *
   *  Check if template project has wiki service enabled
   *  @return boolean
   *
   */
  function templateWikiExists(){
       if($this->tmpl_wiki_exist === null) {
          $res = db_query('SELECT count(*) AS nb FROM wiki_page'
                          .' WHERE group_id='.$this->template_id);
          $this->tmpl_wiki_exist = (db_result($res, 0, 'nb') > 0);
      }
      return $this->tmpl_wiki_exist;
  }  
  
 /**
   *
   *  Create a clone of the template wiki. It includes:
   *  - the clone wiki pages (wiki_page table).
   *
   *  @return boolean.
   *push
   */
  function CloneWiki(){
      $result = db_query(sprintf("SELECT pagename FROM wiki_page WHERE group_id=%d",$this->template_id));
      while($row = db_fetch_array($result)){
          $pagename =  $this->escapeString($row[0]);
	  echo $pagename."<br>";
	  $page_data = $this->getTemplatePageData($pagename);
	  $new_data = $this->createNewPageData($page_data);
	  $this->insertNewWikiPage($new_data, $pagename);
      }
  }
  
 /**
   *
   *  Get page data information from database.
   *
   *  @params pagename : name of any wiki page stored in the db.
   *  @return deserialized page data hash.
   */
  function getTemplatePageData($pagename){
      $result = db_query(sprintf("SELECT pagedata FROM wiki_page WHERE pagename='%s' AND group_id=%d", $pagename, $this->template_id));
      while($page_data = db_fetch_array($result)){
          return $this->_deserialize($page_data['pagedata']);
      }
  }

 /**
   *  Create a new pagedata array.
   *  Up to now, only the creation date timestamp is changed.
   *  Other data (lockinfo and prefs) are copied.
   *
   *  @params data : array of page data
   *  @return data : array of the new page data
   */ 
  function createNewPageData($data){
      if (empty($data)) return array();
      else{
          foreach ($data as $key => $value){
              if ($key == 'date') $data[$key] = time();
          }
          return $data;
      }
  }
  
 /**
   *  Create a clone of a wiki page by inserting a new row in wiki_page table.
   *  
   *  @params array data : array of page data
   *  @params string pagename : escaped wiki page name 
   *  @return int id : id of the created page
   *
   */ 
  function insertNewWikiPage($data, $pagename){
      $result = db_query(sprintf("INSERT INTO wiki_page (pagename, hits, pagedata, group_id)"
				 ."VALUES('%s', %d,  '%s', %d)"
				 , $pagename, 0, $this->_serialize($data), $this->group_id), 1);
  }
  
 /**
   *
   *  Escape special chars in a string,  so that it is safe to place it in a
   *  mysql query.
   *
   *  @params string : the string to escape.
   *  @return string : the string escaped.
   *
   */
  function escapeString($string){
      return db_escape_string($string); 
  }
  
 /**
   * Serialize data
   */
  function _serialize($data) {
      if (empty($data))
          return '';
      assert(is_array($data));
      return serialize($data);
  }

  
 /**
   * Deserialize data
   */
  function _deserialize($data) {
      return empty($data) ? array() : unserialize($data);
  }

}

?>