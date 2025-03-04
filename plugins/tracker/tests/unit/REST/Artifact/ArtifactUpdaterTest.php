<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\Artifact;

use Luracast\Restler\RestException;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentFormatIdentifier;
use Tuleap\Tracker\Artifact\Changeset\NewChangeset;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetCreator;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfForwardLinks;
use Tuleap\Tracker\REST\Artifact\Changeset\Comment\NewChangesetCommentRepresentation;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkChangesetValueBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValueBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\FieldsDataBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactValuesRepresentationBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\CheckArtifactRestUpdateConditionsStub;
use Tuleap\Tracker\Test\Stub\RetrieveForwardLinksStub;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    private const TRACKER_ID  = 34;
    private const FIELD_ID    = 652;
    private const FIELD_VALUE = 'osteolite';

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface & NewChangesetCreator
     */
    private $changeset_creator;
    private RetrieveUsedFieldsStub $fields_retriever;
    private \PFUser $user;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface & Artifact
     */
    private $artifact;

    protected function setUp(): void
    {
        $this->user     = UserTestBuilder::aUser()->build();
        $tracker        = TrackerTestBuilder::aTracker()->withId(self::TRACKER_ID)->build();
        $this->artifact = \Mockery::spy(Artifact::class);
        $this->artifact->shouldReceive('userCanUpdate')->andReturnTrue();
        $this->artifact->shouldReceive('getTracker')->andReturns($tracker);

        $this->changeset_creator = \Mockery::spy(NewChangesetCreator::class);
        $this->fields_retriever  = RetrieveUsedFieldsStub::withFields(
            new \Tracker_FormElement_Field_String(
                self::FIELD_ID,
                34,
                0,
                'irrelevant',
                'Irrelevant',
                'Irrelevant',
                true,
                'P',
                false,
                '',
                1
            )
        );
    }

    private function update(
        ?NewChangesetCommentRepresentation $comment,
        CheckArtifactRestUpdateConditions $check_artifact_rest_update_conditions,
    ): void {
        $string_representation = ArtifactValuesRepresentationBuilder::aRepresentation(self::FIELD_ID)
            ->withValue(self::FIELD_VALUE)
            ->build();
        $values                = [$string_representation];

        $updater = new ArtifactUpdater(
            new FieldsDataBuilder(
                $this->fields_retriever,
                new NewArtifactLinkChangesetValueBuilder(
                    RetrieveForwardLinksStub::withLinks(new CollectionOfForwardLinks([])),
                ),
                new NewArtifactLinkInitialChangesetValueBuilder(),
            ),
            $this->changeset_creator,
            $check_artifact_rest_update_conditions,
        );
        $updater->update($this->user, $this->artifact, $values, $comment);
    }

    public function testUpdateDefaultsCommentToEmptyCommonmarkFormat(): void
    {
        $this->changeset_creator->shouldReceive('create')->withArgs(
            function (
                NewChangeset $new_changeset,
                PostCreationContext $context,
            ) {
                if ($new_changeset->getArtifact() !== $this->artifact) {
                    return false;
                }
                if ($new_changeset->getFieldsData() !== [self::FIELD_ID => self::FIELD_VALUE]) {
                    return false;
                }
                if ($new_changeset->getSubmitter() !== $this->user) {
                    return false;
                }
                if ($context->getImportConfig()->isFromXml()) {
                    return false;
                }
                if ($context->shouldSendNotifications() !== true) {
                    return false;
                }
                $comment = $new_changeset->getComment();
                if ($comment->getBody() !== '') {
                    return false;
                }
                if ($comment->getFormat() !== CommentFormatIdentifier::COMMONMARK) {
                    return false;
                }
                return true;
            }
        )->once();
        $this->update(null, CheckArtifactRestUpdateConditionsStub::allowArtifactUpdate());
    }

    public function testUpdatePassesComment(): void
    {
        $comment_body   = '<p>An HTML comment</p>';
        $comment_format = 'html';
        $comment        = new NewChangesetCommentRepresentation($comment_body, $comment_format);

        $this->changeset_creator->shouldReceive('create')->withArgs(
            function (
                NewChangeset $new_changeset,
                PostCreationContext $context,
            ) use ($comment_body) {
                if ($new_changeset->getArtifact() !== $this->artifact) {
                    return false;
                }
                if ($new_changeset->getSubmitter() !== $this->user) {
                    return false;
                }
                if ($context->getImportConfig()->isFromXml()) {
                    return false;
                }
                if ($context->shouldSendNotifications() !== true) {
                    return false;
                }
                $comment = $new_changeset->getComment();
                if ($comment->getBody() !== $comment_body) {
                    return false;
                }
                if ($comment->getFormat() !== CommentFormatIdentifier::HTML) {
                    return false;
                }
                return true;
            }
        )->once();
        $this->update($comment, CheckArtifactRestUpdateConditionsStub::allowArtifactUpdate());
    }

    public function testUpdateThrowsWhenUserCannotUpdate(): void
    {
        $this->expectException(RestException::class);
        $this->update(
            null,
            CheckArtifactRestUpdateConditionsStub::disallowArtifactUpdate()
        );
    }
}
