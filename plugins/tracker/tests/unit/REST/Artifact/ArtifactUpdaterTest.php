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
use PFUser;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\MockObject\MockObject;
use SebastianBergmann\Comparator\ComparisonFailure;
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
    private const TRACKER_ID  = 34;
    private const FIELD_ID    = 652;
    private const FIELD_VALUE = 'osteolite';

    private NewChangesetCreator&MockObject $changeset_creator;
    private RetrieveUsedFieldsStub $fields_retriever;
    private \PFUser $user;
    private Artifact&MockObject $artifact;

    #[\Override]
    protected function setUp(): void
    {
        $this->user     = UserTestBuilder::aUser()->build();
        $tracker        = TrackerTestBuilder::aTracker()->withId(self::TRACKER_ID)->build();
        $this->artifact = $this->createMock(Artifact::class);
        $this->artifact->method('userCanUpdate')->willReturn(true);
        $this->artifact->method('getTracker')->willReturn($tracker);

        $this->changeset_creator = $this->createMock(NewChangesetCreator::class);
        $this->fields_retriever  = RetrieveUsedFieldsStub::withFields(
            new \Tuleap\Tracker\FormElement\Field\String\StringField(
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
        $this->changeset_creator->expects($this->once())
            ->method('create')
            ->with(
                $this->getNewChangesetMatcher($this->artifact, [self::FIELD_ID => self::FIELD_VALUE], $this->user, '', CommentFormatIdentifier::COMMONMARK),
                $this->getContextMatcher(),
            );
        $this->update(null, CheckArtifactRestUpdateConditionsStub::allowArtifactUpdate());
    }

    public function testUpdatePassesComment(): void
    {
        $comment_body   = '<p>An HTML comment</p>';
        $comment_format = 'html';
        $comment        = new NewChangesetCommentRepresentation($comment_body, $comment_format);

        $this->changeset_creator->expects($this->once())
            ->method('create')
            ->with(
                $this->getNewChangesetMatcher($this->artifact, [self::FIELD_ID => self::FIELD_VALUE], $this->user, $comment_body, CommentFormatIdentifier::HTML),
                $this->getContextMatcher(),
            );
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

    private function getContextMatcher(): Constraint
    {
        return new class extends Constraint
        {
            #[\Override]
            public function matches(mixed $other): bool
            {
                return $other instanceof PostCreationContext &&
                    $other->getImportConfig() &&
                    $other->shouldSendNotifications();
            }

            #[\Override]
            public function toString(): string
            {
                return 'is expected context';
            }
        };
    }

    private function getNewChangesetMatcher(
        Artifact $artifact,
        array $fields_data,
        PFUser $user,
        string $body,
        CommentFormatIdentifier $format,
    ): Constraint {
        return new class (
            $artifact,
            $fields_data,
            $user,
            $body,
            $format,
        ) extends Constraint {
            public function __construct(
                private readonly Artifact $artifact,
                private readonly array $fields_data,
                private readonly PFUser $user,
                private readonly string $body,
                private readonly CommentFormatIdentifier $format,
            ) {
            }

            #[\Override]
            public function evaluate(mixed $other, string $description = '', bool $return_result = false): ?bool
            {
                if (! $other instanceof NewChangeset) {
                    throw new \Exception('NewChangeset expected');
                }

                if ($other->getArtifact() !== $this->artifact) {
                    if ($return_result) {
                        return false;
                    }

                    $this->fail($other, $description, new ComparisonFailure(
                        $this->artifact,
                        $other->getArtifact(),
                        sprintf("'%s'", $this->artifact),
                        sprintf("'%s'", $other->getArtifact()),
                    ));
                    return null;
                }

                if ($other->getFieldsData() !== $this->fields_data) {
                    if ($return_result) {
                        return false;
                    }

                    $this->fail($other, $description, new ComparisonFailure(
                        $this->fields_data,
                        $other->getFieldsData(),
                        sprintf("'%s'", $this->fields_data),
                        sprintf("'%s'", $other->getFieldsData()),
                    ));
                    return null;
                }

                if ($other->getSubmitter() !== $this->user) {
                    if ($return_result) {
                        return false;
                    }

                    $this->fail($other, $description, new ComparisonFailure(
                        $this->user,
                        $other->getSubmitter(),
                        sprintf("'%s'", $this->user),
                        sprintf("'%s'", $other->getSubmitter()),
                    ));
                    return null;
                }

                $comment = $other->getComment();
                if ($comment->getBody() !== $this->body) {
                    if ($return_result) {
                        return false;
                    }

                    $this->fail($other, $description, new ComparisonFailure(
                        $this->body,
                        $comment->getBody(),
                        sprintf("'%s'", $this->body),
                        sprintf("'%s'", $comment->getBody()),
                    ));
                    return null;
                }

                if ($comment->getFormat() !== $this->format) {
                    if ($return_result) {
                        return false;
                    }

                    $this->fail($other, $description, new ComparisonFailure(
                        $this->format,
                        $comment->getFormat(),
                        sprintf("'%s'", $this->format->value),
                        sprintf("'%s'", $comment->getFormat()),
                    ));
                    return null;
                }

                return null;
            }

            #[\Override]
            public function toString(): string
            {
                return 'is expected new changeset';
            }
        };
    }
}
