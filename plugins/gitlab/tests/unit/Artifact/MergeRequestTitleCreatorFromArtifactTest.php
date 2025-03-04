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
 *
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Artifact;

use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MergeRequestTitleCreatorFromArtifactTest extends TestCase
{
    private const ARTIFACT_ID = 123;

    private MergeRequestTitleCreatorFromArtifact $merge_request_title_creator_from_artifact;

    protected function setUp(): void
    {
        $this->merge_request_title_creator_from_artifact = new MergeRequestTitleCreatorFromArtifact();
    }

    public function testMergeRequestTitleIsCreatedFromAnArtifactWithATitle(): void
    {
        $artifact = $this->getArtifact('art title');

        $branch_name = $this->merge_request_title_creator_from_artifact->getMergeRequestTitle($artifact);
        self::assertEquals('Draft: TULEAP-' . self::ARTIFACT_ID . ' art title', $branch_name);
    }

    public function testMergeRequestTitleIsCreatedFromAnArtifactWithoutTitle(): void
    {
        $artifact = $this->getArtifact(null);

        $branch_name = $this->merge_request_title_creator_from_artifact->getMergeRequestTitle($artifact);
        self::assertEquals('Draft: TULEAP-' . self::ARTIFACT_ID, $branch_name);
    }

    public function testMergeRequestTitleIsCreatedFromAnArtifactWithAnEmptyTitle(): void
    {
        $artifact = $this->getArtifact('');

        $branch_name = $this->merge_request_title_creator_from_artifact->getMergeRequestTitle($artifact);
        self::assertEquals('Draft: TULEAP-' . self::ARTIFACT_ID, $branch_name);
    }

    private function getArtifact(?string $title): Artifact
    {
        if ($title !== null) {
            return ArtifactTestBuilder::anArtifact(self::ARTIFACT_ID)->withTitle($title)->build();
        }

        return ArtifactTestBuilder::anArtifact(self::ARTIFACT_ID)->build();
    }
}
