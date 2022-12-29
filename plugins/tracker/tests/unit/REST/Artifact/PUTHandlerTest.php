<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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
use Tracker_NoChangeException;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfForwardLinks;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkChangesetValueBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValueBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\FieldsDataBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveForwardLinksStub;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;

final class PUTHandlerTest extends TestCase
{
    use GlobalResponseMock;

    /**
     * @var ArtifactUpdater&\PHPUnit\Framework\MockObject\MockObject
     */
    private $artifact_updater;

    protected function setUp(): void
    {
        $this->artifact_updater = $this->createMock(ArtifactUpdater::class);
    }

    /**
     * @throws RestException
     */
    private function handle(): void
    {
        $artifact    = ArtifactTestBuilder::anArtifact(1)->build();
        $user        = UserTestBuilder::buildWithDefaults();
        $put_handler = new PUTHandler(
            new FieldsDataBuilder(
                RetrieveUsedFieldsStub::withNoFields(),
                new NewArtifactLinkChangesetValueBuilder(
                    RetrieveForwardLinksStub::withLinks(new CollectionOfForwardLinks([]))
                ),
                new NewArtifactLinkInitialChangesetValueBuilder()
            ),
            $this->artifact_updater
        );
        $put_handler->handle([], $artifact, $user, null);
    }

    public function provideExceptions(): iterable
    {
        yield 'Field is invalid' => [new \Tracker_FormElement_InvalidFieldException(), 400];
        yield 'Field value is invalid' => [new \Tracker_FormElement_InvalidFieldValueException(), 400];
        yield 'Tracker exception' => [new \Tracker_Exception(), 500];
        $other_artifact = ArtifactTestBuilder::anArtifact(83)->build();
        yield 'Attachment is already linked' => [
            new \Tracker_Artifact_Attachment_AlreadyLinkedToAnotherArtifactException(12, $other_artifact),
            500,
        ];
        yield 'Attachment is not found' => [new \Tracker_Artifact_Attachment_FileNotFoundException(), 404];
    }

    /**
     * @dataProvider provideExceptions
     */
    public function testItMapsExceptionsToRestExceptions(\Throwable $throwable, int $expected_status_code): void
    {
        $this->artifact_updater->method('update')->willThrowException($throwable);
        $this->expectException(RestException::class);
        $this->expectExceptionCode($expected_status_code);
        $this->handle();
    }

    public function testItDoesNothingWhenNoChange(): void
    {
        $this->artifact_updater->method('update')->willThrowException(new Tracker_NoChangeException(1, 'art #1'));
        $this->artifact_updater->expects($this->once())->method('update');
        $this->handle();
    }

    public function testItThrows500WhenThereIsAnErrorFeedback(): void
    {
        $this->artifact_updater->method('update')->willThrowException(new \Tracker_Exception());
        $GLOBALS['Response']->method('feedbackHasErrors')->willReturn(true);
        $GLOBALS['Response']->method('getRawFeedback')->willReturn('Aaaah');
        $this->expectException(RestException::class);
        $this->expectExceptionCode(500);
        $this->handle();
    }

    public function testItUpdateArtifactWhenAllLinkKeyIsNotProvided(): void
    {
        $this->artifact_updater->expects($this->once())->method('update');
        $this->handle();
    }
}
