<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values;

use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkTypeProxy;
use Tuleap\ProgramManagement\Tests\Stub\ArtifactIdentifierStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactLinkValueTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const int ARTIFACT_ID = 51;

    public function testItBuildsFromArtifactAndType(): void
    {
        $type  = ArtifactLinkTypeProxy::fromMirrorTimeboxType();
        $value = ArtifactLinkValue::fromArtifactAndType(
            ArtifactIdentifierStub::withId(self::ARTIFACT_ID),
            $type
        );
        self::assertSame(self::ARTIFACT_ID, $value->linked_artifact->getId());
        self::assertSame($type, $value->type);
    }
}
