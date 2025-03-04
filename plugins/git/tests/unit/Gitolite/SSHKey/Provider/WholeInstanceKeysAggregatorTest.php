<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Git\Gitolite\SSHKey\Provider;

use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class WholeInstanceKeysAggregatorTest extends TestCase
{
    public function testItUsesAllKeyProviders(): void
    {
        $gitolite_admin_key = $this->createMock(GitoliteAdmin::class);
        $gerrit_server_keys = $this->createMock(GerritServer::class);
        $user_keys          = $this->createMock(User::class);

        $gitolite_admin_key->expects(self::atLeastOnce())->method('valid');
        $gerrit_server_keys->expects(self::atLeastOnce())->method('valid');
        $user_keys->expects(self::atLeastOnce())->method('valid');
        $gitolite_admin_key->method('rewind');
        $gerrit_server_keys->method('rewind');
        $user_keys->method('rewind');

        $whole_instance_keys = new WholeInstanceKeysAggregator($gitolite_admin_key, $gerrit_server_keys, $user_keys);

        iterator_to_array($whole_instance_keys);
    }
}
