<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\FRS;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactViewTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private int $release_id = 78;

    public function testGetURL(): void
    {
        $artifact      = ArtifactTestBuilder::anArtifact(101)->build();
        $request       = $this->createMock(\Codendi_Request::class);
        $user          = UserTestBuilder::aUser()->build();
        $artifact_view = new ArtifactView($this->release_id, $artifact, $request, $user);

        self::assertSame('/frs/release/78/release-notes', $artifact_view->getURL());
    }
}
