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

use Psr\Log\Test\TestLogger;
use Tuleap\Event\Events\PotentialReferencesReceived;
use Tuleap\GlobalLanguageMock;
use Tuleap\Reference\ReferenceInstance;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Stubs\ExtractReferencesStub;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ArtifactClosingReferencesHandlerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private const FIRST_ARTIFACT_ID  = 111;
    private const SECOND_ARTIFACT_ID = 576;

    private TestLogger $logger;
    private ExtractReferencesStub $reference_extractor;
    private \Project $project;

    protected function setUp(): void
    {
        $this->project = ProjectTestBuilder::aProject()->withId(151)->build();

        $this->logger              = new TestLogger();
        $this->reference_extractor = ExtractReferencesStub::withReferenceInstances(
            $this->getArtifactReferenceInstance('closes', 'art', self::FIRST_ARTIFACT_ID, $this->project),
            $this->getArtifactReferenceInstance('implements', 'story', self::SECOND_ARTIFACT_ID, $this->project),
        );
    }

    public function handlePotentialReferencesReceived(): void
    {
        $text_with_potential_references = sprintf(
            "closes art#%d\nimplements art#%d",
            self::FIRST_ARTIFACT_ID,
            self::SECOND_ARTIFACT_ID,
        );

        $handler = new ArtifactClosingReferencesHandler($this->logger, $this->reference_extractor);
        $handler->handlePotentialReferencesReceived(
            new PotentialReferencesReceived($text_with_potential_references, $this->project)
        );
    }

    public function testItParsesReferences(): void
    {
        $this->handlePotentialReferencesReceived();

        self::assertTrue(
            $this->logger->hasDebugThatContains(
                'Found reference closes art#' . self::FIRST_ARTIFACT_ID . ' with closing keyword'
            )
        );
        self::assertTrue(
            $this->logger->hasDebugThatContains(
                'Found reference implements story#' . self::SECOND_ARTIFACT_ID . ' with closing keyword'
            )
        );
    }

    public function testItDoesNothingWhenNoReferenceIsFound(): void
    {
        $this->reference_extractor = ExtractReferencesStub::withNoReference();

        $this->handlePotentialReferencesReceived();
        self::assertFalse($this->logger->hasDebugRecords());
    }

    public function testItSkipsNonArtifactReferences(): void
    {
        $this->reference_extractor = ExtractReferencesStub::withReferenceInstances(
            $this->getNonArtifactReferenceInstance('doc', 309),
            $this->getNonArtifactReferenceInstance('custom', 95)
        );

        $this->handlePotentialReferencesReceived();
        self::assertFalse($this->logger->hasDebugRecords());
    }

    public function testItSkipsReferencesWhoseContextKeywordIsNotAClosingKeyword(): void
    {
        $this->reference_extractor = ExtractReferencesStub::withReferenceInstances(
            $this->getArtifactReferenceInstance('not_closing', 'art', self::FIRST_ARTIFACT_ID, $this->project),
            $this->getArtifactReferenceInstance('not_closing', 'story', self::SECOND_ARTIFACT_ID, $this->project)
        );

        $this->handlePotentialReferencesReceived();
        self::assertFalse($this->logger->hasDebugRecords());
    }

    public function testItSkipsReferencesToArtifactsFromADifferentProjectThanTheEvent(): void
    {
        $other_project             = ProjectTestBuilder::aProject()->withId(113)->build();
        $this->reference_extractor = ExtractReferencesStub::withReferenceInstances(
            $this->getArtifactReferenceInstance('closes', 'art', self::FIRST_ARTIFACT_ID, $other_project),
            $this->getArtifactReferenceInstance('implements', 'story', self::SECOND_ARTIFACT_ID, $other_project),
        );

        $this->handlePotentialReferencesReceived();
        self::assertFalse($this->logger->hasDebugRecords());
    }

    private function getArtifactReferenceInstance(
        string $context_word,
        string $keyword,
        int $artifact_id,
        \Project $project,
    ): ReferenceInstance {
        $tracker   = TrackerTestBuilder::aTracker()->withProject($project)->build();
        $reference = new \Tracker_Reference($tracker, $keyword);
        return new ReferenceInstance(
            sprintf('%1$s %2$s#%3$d', $context_word, $keyword, $artifact_id),
            $reference,
            (string) self::FIRST_ARTIFACT_ID,
            $keyword,
            (int) $project->getID(),
            $context_word,
        );
    }

    private function getNonArtifactReferenceInstance(string $keyword, int $id): ReferenceInstance
    {
        $reference = new \Reference(
            95,
            $keyword,
            'Not an Artifact Reference',
            'irrelevant',
            'P',
            'irrelevant',
            'plugin_other_document',
            true,
            (int) $this->project->getID()
        );
        return new ReferenceInstance(
            $keyword . '#' . $id,
            $reference,
            (string) $id,
            $keyword,
            (int) $this->project->getID(),
            ''
        );
    }
}
