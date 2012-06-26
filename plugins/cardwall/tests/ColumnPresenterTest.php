
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

require_once dirname(__FILE__).'/../include/ColumnPresenter.class.php';

class ColumnPresenterTest extends TuleapTestCase {
    
    public function itHasColumnDropInto() {
        $swimline_field_values   = array(100, 221);
        $swimline_id             = 3;
        $drop_into               = 'drop-into-3-100 drop-into-3-221';
        $presenter               = new ColumnPresenter($swimline_id, $swimline_field_values);
        $this->assertEqual($drop_into, $presenter->getDropIntoClass());
    }
    
//    public function itHasColumnFieldId() {
//        $this->assert
//    }
}
?>
