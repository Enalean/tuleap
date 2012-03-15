<?php
/**
 *
 * Copyright (C) Andrew Nagy 2009.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once 'Action.php';

class AJAX extends Action {

    function launch()
    {
        header('Content-type: text/xml');
        header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        echo '<?xml version="1.0" encoding="UTF-8"?' . ">\n";
        echo "<AJAXResponse>\n";
        if (is_callable(array($this, $_GET['method']))) {
            $method = $_GET['method']; 
            $this->$method();
        } else {
            echo '<Error>Invalid Method</Error>';
        }
        echo '</AJAXResponse>';
    }    
    
    // Email Record
    function SendEmail()
    {
        require_once 'services/WorldCat/Email.php';

        $emailService = new Email();
        $result = $emailService->sendEmail($_GET['to'], $_GET['from'], $_GET['message']);

        if (PEAR::isError($result)) {
            echo '<result>Error</result><details>' . 
                htmlspecialchars($result->getMessage()) . '</details>';
        } else {
            echo '<result>Done</result>';
        }
    }

    // SMS Record
    function SendSMS()
    {
        require_once 'services/WorldCat/SMS.php';
        $sms = new SMS();
        $result = $sms->sendSMS();
        
        if (PEAR::isError($result)) {
            echo '<result>Error</result>';
        } else {
            echo '<result>Done</result>';
        }
    }
}

?>
