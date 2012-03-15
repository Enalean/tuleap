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
 
require_once 'Record.php';
require_once 'sys/Proxy_Request.php';

class Export extends Record
{
    function launch()
    {
        global $interface;

        $tpl = $this->recordDriver->getExport($_GET['style']);
        if (!empty($tpl)) {
            $interface->display($tpl);
        } else {
            die(translate("Unsupported export format."));
        }
    }
}
?>