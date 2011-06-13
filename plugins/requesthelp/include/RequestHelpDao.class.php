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
class RequestHelpDao extends DataAccessObject {

	/**
	 * Constructor
	 *
	 * @return void
	 */
	function __construct() {
		parent::__construct(CodendiDataAccess::instance());
	}

	/**
	 * Insert the ticket informations in Codex database
	 *
	 * @param Integer $userId      Id of the submitter
	 * @param String  $summary     Ticket summary
	 * @param date    $createDate  Creation date
	 * @param String  $description Ticket description
	 * @param Integer $type        Ticket type
	 * @param Integer $severity    Ticket severity
	 * @param String  $cc          CC mail addresses
	 *
	 * @return Boolean
	 */
	function insertInCodexDB($userId, $summary, $createDate, $description, $type, $severity, $cc) {
		$insert = 'INSERT INTO plugin_request_help'.
                  '(user_id, '.
                  'summary, '.
                  'create_date, '.
                  'description, '.
                  'type, '.
                  'severity, '.
                  'cc '.
                  ')values ('.
		$this->da->escapeInt($userId).', '.
		$this->da->quoteSmart(utf8_encode($summary)).', '.
		$this->da->escapeInt($createDate).', '.
		$this->da->quoteSmart(utf8_encode($description)).', '.
		$this->da->escapeInt($type).', '.
		$this->da->escapeInt($severity).', '.
		$this->da->quoteSmart($cc).')';
		return $this->update($insert);
	}

}

?>