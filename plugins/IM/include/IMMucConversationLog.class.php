<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * This file is licensed under the GNU General Public License version 2. See the file COPYING.
 *
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com> 
 *
 * IMMucConversationLog : manage conversation log between members of the MUC Room
 */

require_once('IMMucLog.class.php');

class IMMucConversationLog extends IMMucLog {

	function __construct($date, $nickname, $username, $message) {
		parent::__construct($date, $nickname, $username, $message);
    }

}

?>
