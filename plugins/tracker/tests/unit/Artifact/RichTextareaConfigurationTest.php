<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RichTextareaConfigurationTest extends TestCase
{
    public function testItBuildsFromNewFollowUpComment(): void
    {
        $tracker  = TrackerTestBuilder::aTracker()->build();
        $artifact = ArtifactTestBuilder::anArtifact(43)->build();
        $user     = UserTestBuilder::buildWithDefaults();
        $comment  = 'photostable surcrue';

        $configuration = RichTextareaConfiguration::fromNewFollowUpComment($tracker, $artifact, $user, $comment);

        self::assertSame($tracker, $configuration->tracker);
        self::assertSame($artifact, $configuration->artifact);
        self::assertSame($user, $configuration->user);
        self::assertSame('tracker_followup_comment_new', $configuration->id);
        self::assertSame('artifact_followup_comment', $configuration->name);
        self::assertSame(8, $configuration->number_of_rows);
        self::assertSame(80, $configuration->number_of_columns);
        self::assertSame($comment, $configuration->content);
        self::assertFalse($configuration->is_required);
    }

    public function testItBuildsFromTextField(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->build();
        $user    = UserTestBuilder::buildWithDefaults();
        $field   = TextFieldBuilder::aTextField(451)->thatIsRequired()
            ->withNumberOfRows(10)
            ->withNumberOfColumns(200)
            ->build();
        $content = 'hygrometrical misexposition';

        $configuration = RichTextareaConfiguration::fromTextField($tracker, null, $user, $field, $content);

        self::assertSame($tracker, $configuration->tracker);
        self::assertNull($configuration->artifact);
        self::assertSame($user, $configuration->user);
        self::assertSame('field_451', $configuration->id);
        self::assertSame('artifact[451][content]', $configuration->name);
        self::assertSame(10, $configuration->number_of_rows);
        self::assertSame(200, $configuration->number_of_columns);
        self::assertSame($content, $configuration->content);
        self::assertTrue($configuration->is_required);
    }
}
