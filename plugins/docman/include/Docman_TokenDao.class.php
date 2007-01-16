<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
//
// $Id$
//

require_once('common/dao/include/DataAccessObject.class.php');

/**
 *  Data Access Object for Docman_Token 
 */
class Docman_TokenDao extends DataAccessObject {
    /**
    * Constructs the Docman_TokenDao
    * @param $da instance of the DataAccess class
    */
    function Docman_TokenDao( & $da ) {
        DataAccessObject::DataAccessObject($da);
    }
    
    /**
    * Searches Docman_Token by Url 
    * @return DataAccessResult
    */
    function & searchUrl($user_id, $token) {
        $sql = sprintf("SELECT url FROM plugin_docman_tokens WHERE user_id = %s AND token = %s",
				$this->da->quoteSmart($user_id),
				$this->da->quoteSmart($token));
        return $this->retrieve($sql);
    }
    
    /**
    * create a row in the table plugin_docman_tokens 
    * @return true or id(auto_increment) if there is no error
    */
    function create($user_id, $token, $url) {
		$sql = sprintf("INSERT INTO plugin_docman_tokens (user_id, token, url) VALUES (%s, %s, %s)",
				$this->da->quoteSmart($user_id),
				$this->da->quoteSmart($token),
				$this->da->quoteSmart($url));
        $inserted = $this->update($sql);
 
        return $inserted;
    }
    
    /**
    * delete a row in the table plugin_docman_tokens
    */
    function delete($user_id, $token) {
        $sql = sprintf("DELETE FROM plugin_docman_tokens WHERE user_id = %s AND token = %s",
				$this->da->quoteSmart($user_id),
				$this->da->quoteSmart($token));
        $deleted = $this->update($sql);
 
        return $deleted;
    }

}


?>