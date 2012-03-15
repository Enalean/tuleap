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

require_once "Action.php";

class Login extends Action
{
    function __construct()
    {
    }

    function launch()
    {
        global $interface;
        global $configArray;

        header('Content-type: text/xml');
        header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

        //print_r($_GET);

        $output = '<?xml version="1.0" encoding="UTF-8"?' . ">\n" .
                  "<AJAXResponse>\n<result>\n";
        //$interface->assign('message', $_GET['message']);
        $interface->assign('title', $_GET['message']);
        $output .= $interface->fetch('AJAX/login.tpl');
        $output .= "\n</result>\n</AJAXResponse>";
        return $output;
    }
}

?>