<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

use Mockery;
use Tracker_ArtifactFactory;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReference;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;

class ArtifactRetrieverTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItThrowsAnExceptionWhenTheArtifactIsNotFound(): void
    {
        $artifact_factory   = Mockery::mock(Tracker_ArtifactFactory::class);
        $artifact_retriever = new ArtifactRetriever($artifact_factory);
        $reference          = new WebhookTuleapReference(10, null);

        $artifact_factory->shouldReceive('getArtifactById')
            ->once()
            ->with(10)
            ->andReturnNull();

        self::expectException(ArtifactNotFoundException::class);

        $artifact_retriever->retrieveArtifactById($reference);
    }

    public function testItReturnsTheArtifact(): void
    {
        $artifact_factory   = Mockery::mock(Tracker_ArtifactFactory::class);
        $artifact_retriever = new ArtifactRetriever($artifact_factory);
        $reference          = new WebhookTuleapReference(10, null);

        $expected_artifact = new Artifact(10, 1, 'submitter', 10050, false);
        $artifact_factory->shouldReceive('getArtifactById')
            ->once()
            ->with(10)
            ->andReturn($expected_artifact);

        $retrieved_artifact = $artifact_retriever->retrieveArtifactById($reference);

        self::assertSame($expected_artifact, $retrieved_artifact);
    }
}
