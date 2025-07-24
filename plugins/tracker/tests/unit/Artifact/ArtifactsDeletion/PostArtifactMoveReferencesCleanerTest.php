<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\ArtifactsDeletion;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Reference\CrossReferencesDao;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfForwardLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfReverseLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ReverseLinkWithNoType;
use Tuleap\Tracker\Artifact\Link\ArtifactLinker;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveArtifactStub;
use Tuleap\Tracker\Test\Stub\RetrieveReverseLinksStub;
use Tuleap\Tracker\Test\Stub\ReverseLinkStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PostArtifactMoveReferencesCleanerTest extends TestCase
{
    private const FIRST_PARENT_ARTIFACT_ID  = 102;
    private const SECOND_PARENT_ARTIFACT_ID = 103;
    private const MOVED_ARTIFACT_ID         = 104;
    private const SOURCE_PROJECT_ID         = 250;
    private const DESTINATION_PROJECT_ID    = 254;

    private ArtifactLinker&MockObject $artifact_linker;
    private Artifact $first_parent;
    private Artifact $second_parent;
    private RetrieveArtifactStub $retrieve_artifact;
    private ReverseLinkStub $first_parent_reverse_link;
    private ReverseLinkStub $second_parent_reverse_link;
    private RetrieveReverseLinksStub $retrieve_reverse_links;
    private Artifact $moved_artifact;
    private \PFUser $user;
    private CrossReferencesDao&MockObject $cross_references_dao;
    private PostArtifactMoveReferenceManager $cross_references_manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->cross_references_dao     = $this->createMock(CrossReferencesDao::class);
        $this->cross_references_manager = new PostArtifactMoveReferenceManager($this->cross_references_dao);
        $this->artifact_linker          = $this->createMock(ArtifactLinker::class);

        $this->first_parent  = ArtifactTestBuilder::anArtifact(self::FIRST_PARENT_ARTIFACT_ID)->build();
        $this->second_parent = ArtifactTestBuilder::anArtifact(self::SECOND_PARENT_ARTIFACT_ID)->build();

        $this->retrieve_artifact = RetrieveArtifactStub::withArtifacts(
            $this->first_parent,
            $this->second_parent,
        );

        $this->first_parent_reverse_link  = ReverseLinkStub::withType(self::FIRST_PARENT_ARTIFACT_ID, ArtifactLinkField::TYPE_IS_CHILD);
        $this->second_parent_reverse_link = ReverseLinkStub::withType(self::SECOND_PARENT_ARTIFACT_ID, ArtifactLinkField::TYPE_IS_CHILD);

        $this->retrieve_reverse_links = RetrieveReverseLinksStub::withLinks(
            new CollectionOfReverseLinks([
                $this->first_parent_reverse_link,
                $this->second_parent_reverse_link,
            ]),
        );

        $this->moved_artifact = ArtifactTestBuilder::anArtifact(self::MOVED_ARTIFACT_ID)->build();
        $this->user           = UserTestBuilder::anActiveUser()->build();
    }

    public function testItDeletesReferencesAndResetsReverseLinksTypesWhenArtifactIsChildOfAnotherOneInAnotherProject(): void
    {
        $this->cross_references_dao->expects($this->once())->method('deleteReferencesWhenArtifactIsSource');
        $this->cross_references_dao->expects($this->once())->method('updateReferencesWhenArtifactIsInTarget');
        $matcher = self::exactly(2);

        $this->artifact_linker->expects($matcher)->method('linkArtifact')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame($this->first_parent, $parameters[0]);
                $this->assertEquals(CollectionOfForwardLinks::fromReverseLink(
                    $this->moved_artifact,
                    ReverseLinkWithNoType::fromReverseLink(
                        $this->first_parent_reverse_link
                    ),
                ), $parameters[1]);
                self::assertSame($this->user, $parameters[2]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame($this->second_parent, $parameters[0]);
                $this->assertEquals(CollectionOfForwardLinks::fromReverseLink(
                    $this->moved_artifact,
                    ReverseLinkWithNoType::fromReverseLink(
                        $this->second_parent_reverse_link
                    )
                ), $parameters[1]);
                self::assertSame($this->user, $parameters[2]);
            }
            return true;
        });

        $cleaner = new PostArtifactMoveReferencesCleaner(
            $this->retrieve_reverse_links,
            $this->artifact_linker,
            $this->retrieve_artifact,
            $this->cross_references_manager
        );

        $cleaner->cleanReferencesAfterArtifactMove(
            $this->moved_artifact,
            DeletionContext::moveContext(self::SOURCE_PROJECT_ID, self::DESTINATION_PROJECT_ID),
            $this->user
        );
    }

    public function testItOnlyDeletesReferencesWhenArtifactHasBeenMovedIntoATrackerOfTheSameProject(): void
    {
        $this->cross_references_dao->expects($this->once())->method('deleteReferencesWhenArtifactIsSource');
        $this->cross_references_dao->expects($this->never())->method('updateReferencesWhenArtifactIsInTarget');
        $this->artifact_linker->expects($this->never())->method('linkArtifact');

        $cleaner = new PostArtifactMoveReferencesCleaner(
            $this->retrieve_reverse_links,
            $this->artifact_linker,
            $this->retrieve_artifact,
            $this->cross_references_manager
        );

        $cleaner->cleanReferencesAfterArtifactMove(
            $this->moved_artifact,
            DeletionContext::moveContext(self::SOURCE_PROJECT_ID, self::SOURCE_PROJECT_ID),
            $this->user
        );
    }
}
