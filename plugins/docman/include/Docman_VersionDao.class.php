<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
//
// 
//

require_once('common/dao/include/DataAccessObject.class.php');

/**
 *  Data Access Object for Docman_VersionDao 
 */
class Docman_VersionDao extends DataAccessObject {
    /**
    * Constructs the Docman_VersionDao
    * @param $da instance of the DataAccess class
    */
    function Docman_VersionDao( & $da ) {
        DataAccessObject::DataAccessObject($da);
    }
    
    /**
    * Gets all tables of the db
    * @return DataAccessResult
    */
    function searchAll() {
        $sql = "SELECT * FROM plugin_docman_version";
        return $this->retrieve($sql);
    }
    
    /**
    * Searches Docman_VersionDao by Id 
    * @return DataAccessResult
    */
    function searchById($id) {
        $sql = sprintf("SELECT item_id, number, user_id, label, changelog, date, filename, filesize, filetype, path FROM plugin_docman_version WHERE id = %s",
				$this->da->quoteSmart($id));
        return $this->retrieve($sql);
    }

    /**
    * Searches Docman_VersionDao by ItemId 
    * @return DataAccessResult
    */
    function searchByItemId($itemId) {
        $sql = sprintf("SELECT id, number, item_id, user_id, label, changelog, date, filename, filesize, filetype, path FROM plugin_docman_version WHERE item_id = %s ORDER BY number DESC",
				$this->da->quoteSmart($itemId));
        return $this->retrieve($sql);
    }

    /**
    * Searches Docman_VersionDao by Number 
    * @return DataAccessResult
    */
    function searchByNumber($item_id, $number) {
        $sql = sprintf("SELECT id, item_id, number, user_id, label, changelog, date, filename, filesize, filetype, path FROM plugin_docman_version WHERE number = %s AND item_id = %s",
				$this->da->quoteSmart($number),
                $this->da->quoteSmart($item_id));
        return $this->retrieve($sql);
    }

    /**
    * Searches Docman_VersionDao by UserId 
    * @return DataAccessResult
    */
    function searchByUserId($userId) {
        $sql = sprintf("SELECT id, item_id, number, label, changelog, date, filename, filesize, filetype, path FROM plugin_docman_version WHERE user_id = %s",
				$this->da->quoteSmart($userId));
        return $this->retrieve($sql);
    }

    /**
    * Searches Docman_VersionDao by Label 
    * @return DataAccessResult
    */
    function searchByLabel($label) {
        $sql = sprintf("SELECT id, item_id, number, user_id, changelog, date, filename, filesize, filetype, path FROM plugin_docman_version WHERE label = %s",
				$this->da->quoteSmart($label));
        return $this->retrieve($sql);
    }

    /**
    * Searches Docman_VersionDao by Changelog 
    * @return DataAccessResult
    */
    function searchByChangelog($changelog) {
        $sql = sprintf("SELECT id, item_id, number, user_id, label, date, filename, filesize, filetype, path FROM plugin_docman_version WHERE changelog = %s",
				$this->da->quoteSmart($changelog));
        return $this->retrieve($sql);
    }

    /**
    * Searches Docman_VersionDao by Date 
    * @return DataAccessResult
    */
    function searchByDate($date) {
        $sql = sprintf("SELECT id, item_id, number, user_id, label, changelog, filename, filesize, filetype, path FROM plugin_docman_version WHERE date = %s",
				$this->da->quoteSmart($date));
        return $this->retrieve($sql);
    }

    /**
    * Searches Docman_VersionDao by Filename 
    * @return DataAccessResult
    */
    function searchByFilename($filename) {
        $sql = sprintf("SELECT id, item_id, number, user_id, label, changelog, date, filesize, filetype, path FROM plugin_docman_version WHERE filename = %s",
				$this->da->quoteSmart($filename));
        return $this->retrieve($sql);
    }

    /**
    * Searches Docman_VersionDao by Filesize 
    * @return DataAccessResult
    */
    function searchByFilesize($filesize) {
        $sql = sprintf("SELECT id, item_id, number, user_id, label, changelog, date, filename, filetype, path FROM plugin_docman_version WHERE filesize = %s",
				$this->da->quoteSmart($filesize));
        return $this->retrieve($sql);
    }

    /**
    * Searches Docman_VersionDao by Filetype 
    * @return DataAccessResult
    */
    function searchByFiletype($filetype) {
        $sql = sprintf("SELECT id, item_id, number, user_id, label, changelog, date, filename, filesize, path FROM plugin_docman_version WHERE filetype = %s",
				$this->da->quoteSmart($filetype));
        return $this->retrieve($sql);
    }

    /**
    * Searches Docman_VersionDao by Path 
    * @return DataAccessResult
    */
    function searchByPath($path) {
        $sql = sprintf("SELECT id, item_id, number, user_id, label, changelog, date, filename, filesize, filetype FROM plugin_docman_version WHERE path = %s",
				$this->da->quoteSmart($path));
        return $this->retrieve($sql);
    }


    /**
    * create a row in the table plugin_docman_version 
    * @return true or id(auto_increment) if there is no error
    */
    function create($item_id, $number, $user_id, $label, $changelog, $date, $filename, $filesize, $filetype, $path) {
		$sql = sprintf("INSERT INTO plugin_docman_version (item_id, number, user_id, label, changelog, date, filename, filesize, filetype, path) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
				$this->da->quoteSmart($item_id),
				$this->da->quoteSmart($number),
				$this->da->quoteSmart($user_id),
				$this->da->quoteSmart($label, array('force_string' => true)),
				$this->da->quoteSmart($changelog),
				$this->da->quoteSmart($date),
				$this->da->quoteSmart($filename),
				$this->da->quoteSmart($filesize),
				$this->da->quoteSmart($filetype),
				$this->da->quoteSmart($path));
        return $this->_createAndReturnId($sql);
    }
    function createFromRow($row) {
        if (!isset($row['date']) || $row['date'] == '') {
            $row['date'] = time();
        }
        $arg    = array();
        $values = array();
        $params = array('force_string' => false);
        $cols   = array('item_id', 'number', 'user_id', 'label', 'changelog', 'date', 'filename', 'filesize', 'filetype', 'path');
        foreach ($row as $key => $value) {
            if (in_array($key, $cols)) {
                $arg[]    = $key;
                $params['force_string'] = ($key == 'label');
                $values[] = $this->da->quoteSmart($value, $params);
            }
        }
        if (count($arg)) {
            $sql = 'INSERT INTO plugin_docman_version '
                .'('.implode(', ', $arg).')'
                .' VALUES ('.implode(', ', $values).')';
            return $this->_createAndReturnId($sql);
        } else {
            return false;
        }
    }
    function _createAndReturnId($sql) {
        $inserted = $this->update($sql);
        if ($inserted) {
            $dar = $this->retrieve("SELECT LAST_INSERT_ID() AS id");
            if ($row = $dar->getRow()) {
                $inserted = $row['id'];
            } else {
                $inserted = $dar->isError();
            }
        }
        return $inserted;
    }

}


?>