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
 
require_once 'CatalogConnection.php';

require_once 'Action.php';

class Hold extends Action
{
    var $catalog;

    function launch()
    {
        global $configArray;

        try {
            $this->catalog = new CatalogConnection($configArray['Catalog']['driver']);
        } catch (PDOException $e) {
            // What should we do with this error?
            if ($configArray['System']['debug']) {
                echo '<pre>';
                echo 'DEBUG: ' . $e->getMessage();
                echo '</pre>';
            }
        }

        // Check How to Process Hold
        if (method_exists($this->catalog->driver, 'placeHold')) {
            $this->placeHold();
        } elseif (method_exists($this->catalog->driver, 'getHoldLink')) {
            // Redirect user to Place Hold screen on ILS OPAC
            $link = $this->catalog->getHoldLink($_GET['id']);
            if (!PEAR::isError($link)) {
                header('Location:' . $link);
            } else {
                PEAR::raiseError($link);
            }
        } else {
            PEAR::raiseError(new PEAR_Error('Cannot Process Place Hold - ILS Not Supported'));
        }
    }
    
    function placeHold()
    {
        global $interface;
        global $configArray;
        
        $interface->assign('id', $_GET['id']);
        $holding = $this->catalog->getHolding($_GET['id']);
        if (PEAR::isError($holding)) {
            PEAR::raiseError($holding);
        }
        $interface->assign('holding', $holding);

        if (isset($_POST['id'])) {
            $patron = $this->catalog->patronLogin($_POST['id'], $_POST['lname']);
            if ($patron && !PEAR::isError($patron)) {
                $this->catalog->placeHold($_GET['id'], $patron['id'], $_POST['comment'], $type);
            } else {
                $interface->assign('message', 'Incorrect Patron Information');
            }
        }

        $class = $configArray['Index']['engine'];
        $db = new $class($configArray['Index']['url']);
        $record = $db->getRecord('id:' . $_GET['id']);
        if ($record) {
            $interface->assign('record', $record);
        } else {
            PEAR::raiseError(new PEAR_Error(translate('Cannot find record')));
        }

        $interface->setTemplate('hold.tpl');

        $interface->display('layout.tpl');
    }
}

?>