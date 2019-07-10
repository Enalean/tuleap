<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Test\Builders;

class UserTestBuilder
{
    private $params = array('language_id' => 'en_US');

    public static function aUser() : self
    {
        return new self();
    }

    public function withUserName(string $name)
    {
        $this->params['user_name'] = $name;
        return $this;
    }

    public function withId(int $id)
    {
        $this->params['user_id'] = $id;
        return $this;
    }

    public function withLdapId(string $id)
    {
        $this->params['ldap_id'] = $id;
        return $this;
    }

    public function build()
    {
        return new \PFUser($this->params);
    }
}
