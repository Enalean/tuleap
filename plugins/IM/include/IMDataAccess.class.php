<?php
/**
 * Copyright (c) Enalean, 2013-2018. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use ParagonIE\EasyDB\Factory;

class IMDataAccess extends DataAccess {
    
    public function __construct(DataAccessCredentials $credentials)
    {
        parent::__construct($credentials);
    }
    
    function instance($controler) {
        static $_imdataaccess_instance;
        if ($_imdataaccess_instance === null) {
            $plugin   = $controler->getPlugin();
            $etc_root = $plugin->getPluginEtcRoot();

            include_once($etc_root . '/database_im.inc');
            $credentials = new DataAccessCredentials($im_dbhost, $im_dbuser, $im_dbpasswd, $im_dbname);


            if (! ForgeConfig::get('fallback_to_deprecated_mysql_api')) {
                $db                     = Factory::create(
                    'mysql:host=' . $credentials->getHost() . ';dbname=' . $credentials->getDatabaseName(),
                    $im_dbuser,
                    $im_dbpasswd
                );
                $_imdataaccess_instance = new \Tuleap\DB\Compat\Legacy2018\CompatPDODataAccess($db);
            } else {
                $_imdataaccess_instance = new IMDataAccess($credentials);
            }
        }
        return $_imdataaccess_instance;
    }
}
