<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Adapter\Document\Section;

use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\REST\v1\RequiredArtifactInformationTestBuilder;
use Tuleap\Artidoc\Stubs\BuildRequiredArtifactInformationStub;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

final class RequiredSectionInformationForCreationCollectorTest extends TestCase
{
    private const ARTIFACT_ID = 101;
    public function testItCollectsRequiredSectionInformationForCreation(): void
    {
        $required_artifact_information = RequiredArtifactInformationTestBuilder::fromArtifact(
            ArtifactTestBuilder::anArtifact(self::ARTIFACT_ID)->build(),
        )->build();

        $collector = new RequiredSectionInformationForCreationCollector(
            UserTestBuilder::buildWithDefaults(),
            BuildRequiredArtifactInformationStub::withRequiredArtifactInformation([
                self::ARTIFACT_ID => $required_artifact_information,
            ])
        );

        $collected = $collector->getCollectedRequiredSectionInformationForCreation(self::ARTIFACT_ID);
        self::assertTrue(Result::isErr($collected));

        $result = $collector->collectRequiredSectionInformationForCreation(
            new ArtidocWithContext(new ArtidocDocument([])),
            self::ARTIFACT_ID,
        );

        self::assertTrue(Result::isOk($result));

        $collected = $collector->getCollectedRequiredSectionInformationForCreation(self::ARTIFACT_ID);
        self::assertTrue(Result::isOk($collected));
        self::assertSame($required_artifact_information, $collected->value);
    }

    public function testItCollectsNothingIfNoRequiredSectionInformationForCreation(): void
    {
        $collector = new RequiredSectionInformationForCreationCollector(
            UserTestBuilder::buildWithDefaults(),
            BuildRequiredArtifactInformationStub::withoutRequiredArtifactInformation(),
        );

        $collected = $collector->getCollectedRequiredSectionInformationForCreation(self::ARTIFACT_ID);
        self::assertTrue(Result::isErr($collected));

        $result = $collector->collectRequiredSectionInformationForCreation(
            new ArtidocWithContext(new ArtidocDocument([])),
            self::ARTIFACT_ID,
        );

        self::assertTrue(Result::isErr($result));

        $collected = $collector->getCollectedRequiredSectionInformationForCreation(self::ARTIFACT_ID);
        self::assertTrue(Result::isErr($collected));
    }
}
