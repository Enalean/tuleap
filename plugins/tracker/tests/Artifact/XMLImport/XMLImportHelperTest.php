<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

class Tracker_Artifact_XMLImport_XMLImportHelperTest extends TuleapTestCase {

   public function testItImportsAnonymousUser() {
       $user_manager  = mock('UserManager');
       $import_helper = new Tracker_XMLImport_XMLImportHelper($user_manager);
       stub($user_manager)->getUserByIdentifier()->returns(null);
       stub($user_manager)->getUserAnonymous()->returns(new PFUser());

       $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
<user>veloc@dino.com</user>');

       $user = $import_helper->getUser($xml);

       $this->assertEqual($user->getEmail(), 'veloc@dino.com');
   }
}