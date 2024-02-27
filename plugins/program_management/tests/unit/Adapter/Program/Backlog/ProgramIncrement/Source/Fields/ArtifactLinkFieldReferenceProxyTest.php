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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Fields;

use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;

final class ArtifactLinkFieldReferenceProxyTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIELD_ID    = 157;
    private const FIELD_LABEL = 'Fides';

    public function testItBuildsFromTrackerField(): void
    {
        $field = ArtifactLinkFieldReferenceProxy::fromTrackerField(
            ArtifactLinkFieldBuilder::anArtifactLinkField(self::FIELD_ID)->withLabel(self::FIELD_LABEL)->build()
        );
        self::assertSame(self::FIELD_ID, $field->getId());
        self::assertSame(self::FIELD_LABEL, $field->getLabel());
    }
}
