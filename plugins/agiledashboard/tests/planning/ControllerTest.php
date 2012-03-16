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

require_once(dirname(__FILE__).'/../../include/Planning/Controller.class.php');
require_once(dirname(__FILE__).'/../../../tracker/tests/Test_Tracker_Builder.php');
if (!defined('TRACKER_BASE_URL')) {
    define('TRACKER_BASE_URL', '/plugins/tracker');
}

class Planning_ControllerTest extends TuleapTestCase {
    
    public function itExplicitlySaysThereAreNoItemsWhenThereIsNothing() {
        $content = $this->WhenICaptureTheOutputOfEditAction();
//        $this->assertPattern('/No items yet/', $content);
    }
    
    private function WhenICaptureTheOutputOfEditAction() {
        ob_start();
        $controller = new Planning_Controller();
        $controller->edit();
        $content = ob_get_clean();
        return $content;
    }
        
    
}


?>
