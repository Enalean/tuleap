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

require_once 'Record.php';

class Holdings extends Record
{
    function launch()
    {
        global $interface;
        global $configArray;

        // Do not cache holdings page
        $interface->caching = 0;

        $interface->setPageTitle(translate('Holdings') . ': ' . $this->recordDriver->getBreadcrumb());
        $interface->assign('holdingsMetadata', $this->recordDriver->getHoldings());

        try {
            $catalog = new CatalogConnection($configArray['Catalog']['driver']);
        } catch (PDOException $e) {
            // What should we do with this error?
            if ($configArray['System']['debug']) {
                echo '<pre>';
                echo 'DEBUG: ' . $e->getMessage();
                echo '</pre>';
            }
        }

        // Get Holdings Data
        $id = $this->recordDriver->getUniqueID();
        if ($catalog->status) {
            $result = $catalog->getHolding($id);
            if (PEAR::isError($result)) {
                PEAR::raiseError($result);
            }
            $holdings = array();
            if (count($result)) {
              foreach ($result as $copy) {
                  if ($copy['location'] != 'World Wide Web') {
                      $holdings[$copy['location']][] = $copy;
                  }
              }
            }
            $interface->assign('holdings', $holdings);
    
            // Get Acquisitions Data
            $result = $catalog->getPurchaseHistory($id);
            if (PEAR::isError($result)) {
                PEAR::raiseError($result);
            }
            $interface->assign('history', $result);
        }
        
        $interface->assign('subTemplate', 'view-holdings.tpl');
        $interface->setTemplate('view.tpl');
        
        // Display Page
        $interface->display('layout.tpl');
    }
}

?>
