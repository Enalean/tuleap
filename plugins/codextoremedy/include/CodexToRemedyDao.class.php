<?php
/**
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/
 */
require_once('common/dao/include/DataAccessObject.class.php');

/**
 * Dao of the plugin
 */
class CodexToRemedyDao extends DataAccessObject {

    /**
     * Constructor
     *
     * @return void
     */
    function __construct() {
        parent::__construct( CodendiDataAccess::instance() );
    }

    /**
     * Insert the ticket informations in Codex database
     *
     * @param Integer $id
     * @param Integer $userId
     * @param String  $summary
     * @param date    $createDate
     * @param String  $description
     * @param Integer $type
     * @param Integer $severity
     *
     * @return Boolean
     */
    function insertInCodexDB($id, $userId, $summary, $createDate, $description, $type, $severity, $cc) {
        $select = 'SELECT NULL FROM plugin_codex_to_remedy WHERE id = '.$this->da->escapeInt($id);
        $res = $this->retrieve($select);
        if($res && !$res->isError() && $res->rowCount() == 0) {
            $insert = 'INSERT INTO plugin_codex_to_remedy'.
                      '(id, '.
                      'user_id, '.
                      'summary, '.
                      'create_date, '.
                      'description, '.
                      'type, '.
                      'severity, '.
                      'cc '.
                      ')values ('.
                      $this->da->escapeInt($id).', '.
                      $this->da->escapeInt($userId).', '.
                      $this->da->quoteSmart($summary).', '.
                      $this->da->escapeInt($createDate).', '.
                      $this->da->quoteSmart($description).', '.
                      $this->da->escapeInt($type).', '.
                      $this->da->escapeInt($severity).', '.
                      $this->da->quoteSmart($cc).')';
            return $this->update($insert);
        } else {
            return false;
        }
    }

}

?>