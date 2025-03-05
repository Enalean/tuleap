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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxArtifactLinkType;
use Tuleap\ProgramManagement\Tests\Stub\ArtifactIdentifierStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactLinkValueFormatterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TIMEBOX_ID = 90;

    public function getFormatter(): ArtifactLinkValueFormatter
    {
        return new ArtifactLinkValueFormatter();
    }

    public function testItFormatsNullValueToAnEmptyValue(): void
    {
        $formatted_value = $this->getFormatter()->formatForTrackerPlugin(null);
        self::assertArrayHasKey('new_values', $formatted_value);
        self::assertArrayHasKey('types', $formatted_value);
        self::assertSame('', $formatted_value['new_values']);
        self::assertEmpty($formatted_value['types']);
    }

    public function testItFormatsValueToArrayExpectedByTrackerPluginAPI(): void
    {
        $value           = ArtifactLinkValue::fromArtifactAndType(
            ArtifactIdentifierStub::withId(self::TIMEBOX_ID),
            ArtifactLinkTypeProxy::fromMirrorTimeboxType()
        );
        $formatted_value = $this->getFormatter()->formatForTrackerPlugin($value);
        self::assertArrayHasKey('new_values', $formatted_value);
        self::assertArrayHasKey('types', $formatted_value);
        self::assertSame((string) self::TIMEBOX_ID, $formatted_value['new_values']);
        self::assertArrayHasKey((string) self::TIMEBOX_ID, $formatted_value['types']);
        self::assertSame([(string) self::TIMEBOX_ID => TimeboxArtifactLinkType::ART_LINK_SHORT_NAME], $formatted_value['types']);
    }
}
