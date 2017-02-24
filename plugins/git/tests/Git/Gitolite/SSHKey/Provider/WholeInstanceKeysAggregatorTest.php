<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Git\Gitolite\SSHKey\Provider;

class WholeInstanceKeysAggregatorTest extends \TuleapTestCase
{
    public function itUsesAllKeyProviders()
    {
        $gitolite_admin_key = mock('Tuleap\Git\Gitolite\SSHKey\Provider\GitoliteAdmin');
        $gerrit_server_keys = mock('Tuleap\Git\Gitolite\SSHKey\Provider\GerritServer');
        $user_keys          = mock('Tuleap\Git\Gitolite\SSHKey\Provider\User');

        $whole_instance_keys = new WholeInstanceKeysAggregator($gitolite_admin_key, $gerrit_server_keys, $user_keys);

        $gitolite_admin_key->expectAtLeastOnce('valid');
        $gerrit_server_keys->expectAtLeastOnce('valid');
        $user_keys->expectAtLeastOnce('valid');
        iterator_to_array($whole_instance_keys);
    }
}
