<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once(dirname(__FILE__).'/../builders/all.php');
require_once TRACKER_BASE_DIR.'/Tracker/SOAPServer.class.php';

class Tracker_SOAPServer_CriteriaTransformTest extends TuleapTestCase {

    public function itConvertsFloat() {
        $criteria = array(
            array('name'  => 'float_field',
                  'value' => '>3.14'),
            /*array('field_name'  => 'date_field',
                  'op'          => '',
                  'from_value'  => '1324654654654',
                  'to_value'    => '1324654654654'),*/
        );

        $current_user        = mock('User');
        $tracker             = aMockTracker()->build();
        $permissions_manager = mock('PermissionsManager');
        $dao                 = mock('Tracker_ReportDao');
        stub($dao)->searchMatchingIds()->returnsEmptyDar();
        $soap_report = new Tracker_Report_SOAP($current_user, $tracker, $permissions_manager, $dao);
        
        $server = new Tracker_SOAPServer($soap_report);
        $server->getArtifacts(null, null, null, $criteria, null, null);
    }
}

?>
