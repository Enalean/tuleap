<?php
/**
 *
 * Copyright (C) Villanova University 2007.
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

    function AJAX()
    {
    }

    function launch()
    {
        header ('Content-type: text/xml');
        header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        $xml = '<?xml version="1.0" encoding="UTF-8"?' . ">\n" .
               "<AJAXResponse>\n";
        if (is_callable(array($this, $_GET['method']))) {
            $xml .= $this->$_GET['method']();
        } else {
            $xml .= '<Error>Invalid Method</Error>';
        }
        $xml .= '</AJAXResponse>';
        
        echo $xml;
    }
    
    // Create new list
    function AddList()
    {
        require_once 'services/MyResearch/ListEdit.php';

        if (UserAccount::isLoggedIn()) {
            $listService = new ListEdit();
            $result = $listService->addList();
            if (!PEAR::isError($result)) {
                $xml = "<result>Done</result>";
                $xml .= "<newId>$result</newId>";
            } else {
                $error = $result->getMessage();
                if (empty($error)) {
                    $error = 'Error';
                }
                $xml = "<result>" . htmlspecialchars(translate($error)) . "</result>";
            }
        } else {
            $xml = "<result>Unauthorized</result>";
        }

        return $xml;
    }
}
?>
