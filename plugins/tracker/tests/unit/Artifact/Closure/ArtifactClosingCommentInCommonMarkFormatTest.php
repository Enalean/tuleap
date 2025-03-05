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

namespace Tuleap\Tracker\Artifact\Closure;

use PHPUnit\Framework\Attributes\DataProvider;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Test\Stubs\ReferenceStringStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactClosingCommentInCommonMarkFormatTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const USERNAME_CLOSING_THE_ARTIFACT = 'lgilhooly';
    private const TRACKER_SHORTNAME             = 'tracker_isetta';
    private const ORIGIN_REFERENCE              = 'git #pyronomics/614b83';

    private function buildComment(?ClosingKeyword $keyword): ArtifactClosingCommentInCommonMarkFormat
    {
        $tracker = TrackerTestBuilder::aTracker()->withShortName(self::TRACKER_SHORTNAME)->build();

        return ArtifactClosingCommentInCommonMarkFormat::fromParts(
            self::USERNAME_CLOSING_THE_ARTIFACT,
            $keyword,
            $tracker,
            ReferenceStringStub::fromString(self::ORIGIN_REFERENCE)
        );
    }

    public static function provideClosingKeywords(): array
    {
        return [
            'empty comment when keyword is null' => [null, ''],
            'comment with resolves'              => [
                ClosingKeyword::buildResolves(),
                sprintf('Solved by %s with %s.', self::USERNAME_CLOSING_THE_ARTIFACT, self::ORIGIN_REFERENCE),
            ],
            'comment with closes'                => [
                ClosingKeyword::buildCloses(),
                sprintf('Closed by %s with %s.', self::USERNAME_CLOSING_THE_ARTIFACT, self::ORIGIN_REFERENCE),
            ],
            'comment with implements'            => [
                ClosingKeyword::buildImplements(),
                sprintf('Implemented by %s with %s.', self::USERNAME_CLOSING_THE_ARTIFACT, self::ORIGIN_REFERENCE),
            ],
            'comments with fixes'                => [
                ClosingKeyword::buildFixes(),
                sprintf(
                    '%s fixed by %s with %s.',
                    self::TRACKER_SHORTNAME,
                    self::USERNAME_CLOSING_THE_ARTIFACT,
                    self::ORIGIN_REFERENCE
                ),
            ],
        ];
    }

    #[DataProvider('provideClosingKeywords')]
    public function testItBuildsComment(?ClosingKeyword $keyword, string $expected_comment): void
    {
        self::assertSame($expected_comment, $this->buildComment($keyword)->getBody());
    }
}
