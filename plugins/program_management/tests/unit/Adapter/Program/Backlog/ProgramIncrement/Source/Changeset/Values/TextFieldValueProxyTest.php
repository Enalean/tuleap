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

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TextFieldValueProxyTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuildsFromChangesetValue(): void
    {
        $artifact  = ArtifactTestBuilder::anArtifact(82)->build();
        $changeset = new \Tracker_Artifact_Changeset(6095, $artifact, 110, 1234567890, null);

        $multiline_text = <<<EOT
        osmosis connatural
        ruptile recedent
        EOT;


        $changeset_value = new \Tracker_Artifact_ChangesetValue_Text(
            7094,
            $changeset,
            $this->getTextField(),
            true,
            $multiline_text,
            \Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT
        );

        $value = TextFieldValueProxy::fromChangesetValue($changeset_value);
        self::assertSame($multiline_text, $value->getValue());
        self::assertSame('text', $value->getFormat());
    }

    private function getTextField(): \Tracker_FormElement_Field_Text
    {
        $project = ProjectTestBuilder::aProject()->withId(115)->build();
        $tracker = TrackerTestBuilder::aTracker()->withProject($project)->build();
        $field   = new \Tracker_FormElement_Field_Text(
            168,
            84,
            1,
            'irrelevant',
            'Irrelevant',
            'Irrelevant',
            true,
            'P',
            true,
            '',
            1
        );
        $field->setTracker($tracker);
        return $field;
    }
}
