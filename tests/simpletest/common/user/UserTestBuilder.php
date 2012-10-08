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

require_once 'common/user/User.class.php';

/**
 * Ease creation of User object
 *
 * $user = aUser()->withId(123)->withUserName('pouet')->build();
 *
 * @return \UserTestBuilder
 */
function aUser() {
    return new UserTestBuilder();
}

function anAnonymousUser() {
    return aUser()->withId(0);
}

class UserTestBuilder {
    private $params = array('language_id' => 'en_US');

    function withUserName($name) {
        $this->params['user_name'] = $name;
        return $this;
    }

    function withId($id) {
        $this->params['user_id'] = $id;
        return $this;
    }

    function withAuthorizedKeysArray(array $keys) {
        $this->params['authorized_keys'] = implode('###', $keys);
        return $this;
    }

    function withUnixStatus($status) {
        $this->params['unix_status'] = $status;
        return $this;
    }

    function build() {
        return new User($this->params);
    }
}

?>
