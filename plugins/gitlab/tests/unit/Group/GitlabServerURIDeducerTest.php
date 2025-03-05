<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Group;

use Tuleap\Gitlab\Test\Builder\GroupLinkBuilder;
use Tuleap\Http\HTTPFactoryBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitlabServerURIDeducerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const WEB_URL = 'https://user@my.gitlab.example.com:7010/groups/cottonbush/nonannuitant#enzone?id=1';

    public function testItDeducesGitlabServerURIFromAGroupLink(): void
    {
        $group_link = GroupLinkBuilder::aGroupLink(20)
            ->withWebURL(self::WEB_URL)
            ->build();

        $deducer = new GitlabServerURIDeducer(HTTPFactoryBuilder::URIFactory());

        self::assertSame('https://user@my.gitlab.example.com:7010', (string) $deducer->deduceServerURI($group_link));
    }
}
