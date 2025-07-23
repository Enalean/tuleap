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

namespace Tuleap\Tracker\Artifact\Changeset;

use PHPUnit\Framework\Attributes\DataProvider;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentFormatIdentifier;
use Tuleap\Tracker\Artifact\Changeset\Comment\NewComment;
use Tuleap\Tracker\Artifact\Exception\FieldValidationException;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Stub\CreateNewChangesetStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CommentOnlyChangesetCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const COMMENT_BODY         = 'roisteringly reconvalescent';
    private const SUBMISSION_TIMESTAMP = 1411230972; // 2014-09-20T18:36:12
    private const NEW_CHANGESET_ID     = 6901;
    private const ARTIFACT_ID          = 84;
    private const USER_ID              = 102;

    private CreateNewChangesetStub $inner_creator;

    #[\Override]
    protected function setUp(): void
    {
        $this->inner_creator = CreateNewChangesetStub::withReturnChangeset(
            ChangesetTestBuilder::aChangeset(self::NEW_CHANGESET_ID)
                ->submittedOn(self::SUBMISSION_TIMESTAMP)
                ->submittedBy(self::USER_ID)
                ->build()
        );
    }

    /**
     * @return Ok<\Tracker_Artifact_Changeset> | Err<Fault>
     */
    private function createCommentOnlyChangeset(): Ok|Err
    {
        $new_comment = NewComment::fromParts(
            self::COMMENT_BODY,
            CommentFormatIdentifier::COMMONMARK,
            UserTestBuilder::aUser()->withId(self::USER_ID)->build(),
            self::SUBMISSION_TIMESTAMP,
            []
        );

        $creator = new CommentOnlyChangesetCreator($this->inner_creator);
        return $creator->createCommentOnlyChangeset($new_comment, ArtifactTestBuilder::anArtifact(self::ARTIFACT_ID)->build());
    }

    public function testItCreatesAChangesetHoldingOnlyAComment(): void
    {
        $result = $this->createCommentOnlyChangeset();

        self::assertTrue(Result::isOk($result));
        $changeset = $result->value;
        self::assertSame(self::NEW_CHANGESET_ID, $changeset->id);
        self::assertSame(self::SUBMISSION_TIMESTAMP, (int) $changeset->getSubmittedOn());
        self::assertSame(self::USER_ID, (int) $changeset->getSubmittedBy());
    }

    public function testItReturnsErrWhenThereIsAnIssueDuringChangesetCreation(): void
    {
        $this->inner_creator = CreateNewChangesetStub::withNullReturnChangeset();

        $result = $this->createCommentOnlyChangeset();

        self::assertTrue(Result::isErr($result));
    }

    public static function dataProviderExceptions(): iterable
    {
        return [
            'with No change error'        => [new \Tracker_NoChangeException(
                self::ARTIFACT_ID,
                'story #' . self::ARTIFACT_ID
            ),
            ],
            'with DB error'               => [new \Tracker_ChangesetNotCreatedException()],
            'with field validation error' => [new FieldValidationException([])],
        ];
    }

    #[DataProvider('dataProviderExceptions')]
    public function testItReturnsErrWhenThereIsAnExceptionDuringChangesetCreation(\Throwable $exception): void
    {
        $this->inner_creator = CreateNewChangesetStub::withException($exception);

        $result = $this->createCommentOnlyChangeset();

        self::assertTrue(Result::isErr($result));
    }
}
