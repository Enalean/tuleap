<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'common/svn/SVN_SoapRevisionDecorator.class.php';

class SVN_SoapRevisionDecoratorTest extends TuleapTestCase {
    
    public function itFormatsAnSvnRevisionToMatchTheSoapApi() {
        $decorator    = new SVN_SoapRevisionDecorator();
        $raw_revision = array('1', '1', 'Added README', '1337140135', '108');
        
        $decorated_revision = $decorator->decorate($raw_revision);
        
        $this->assertEqual($decorated_revision['revision'], '1');
        $this->assertEqual($decorated_revision['author'], '108');
        $this->assertEqual($decorated_revision['date'], '1337140135');
        $this->assertEqual($decorated_revision['message'], 'Added README');
    }
}
?>
