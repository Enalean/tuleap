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

require_once 'common/project/UGroup.class.php';

class UGroupTest extends TuleapTestCase {
    
    function itAddUserIntoStaticGroupWithLegacyMethod() {
        $ugroup_id = 200;
        $group_id  = 300;
        $user_id   = 400;
        
        $ugroup = TestHelper::getPartialMock('UGroup', array('addUserToGroup'));
        $ugroup->__construct(array('ugroup_id' => $ugroup_id, 'group_id' => $group_id));
        
        $user = stub('User')->getId()->returns($user_id);
        
        $ugroup->expectOnce('addUserToGroup', array($group_id, $ugroup_id, $user_id));
        
        $ugroup->addUser($user);
    }
}

?>
