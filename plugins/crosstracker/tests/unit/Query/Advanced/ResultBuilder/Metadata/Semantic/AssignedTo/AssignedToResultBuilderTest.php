<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Metadata\Semantic\AssignedTo;

use PHPUnit\Framework\MockObject\Stub;
use Tracker_FormElement_Field_List_Bind;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\UserListRepresentation;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\UserRepresentation;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValue;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use Tuleap\Tracker\Semantic\Contributor\TrackerSemanticContributor;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\SelectboxFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveArtifactStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AssignedToResultBuilderTest extends TestCase
{
    private \PFUser $user;
    private RetrieveUserByIdStub $user_retriever;
    private \UserHelper&Stub $user_helper;
    private \Tuleap\Tracker\Artifact\Artifact $artifact;
    private RetrieveArtifactStub $retrieve_artifact;
    private TrackerSemanticContributor&\PHPUnit\Framework\MockObject\MockObject $semantic_contributor;
    private \Tuleap\Tracker\FormElement\Field\List\SelectboxField|\Tuleap\Tracker\FormElement\Field\List\MultiSelectboxField $assigned_to_field;

    #[\Override]
    protected function setUp(): void
    {
        $this->user           = UserTestBuilder::aRandomActiveUser()->withUserName('Jean Eude')->withRealName('Jean Eude')->withAvatarUrl('example.com/my/avatar/url')->build();
        $this->user_retriever = RetrieveUserByIdStub::withUser($this->user);
        $this->user_helper    = $this->createStub(\UserHelper::class);
        $this->user_helper->method('getDisplayNameFromUser');
        $this->artifact             = ArtifactTestBuilder::anArtifact(13)->inTracker(TrackerTestBuilder::aTracker()->build())->build();
        $this->retrieve_artifact    = RetrieveArtifactStub::withArtifacts($this->artifact);
        $this->semantic_contributor = $this->createMock(TrackerSemanticContributor::class);
    }

    #[\Override]
    protected function tearDown(): void
    {
        \Tuleap\Tracker\Semantic\Contributor\TrackerSemanticContributor::clearInstances();
    }

    public function testItThrowsAnExceptionWhenUserIsNotFoundInTuleap(): void
    {
        $select_results = [['id' => $this->artifact->getId(), '@assigned_to' => $this->user->getId()]];
        $builder        = new AssignedToResultBuilder(RetrieveUserByIdStub::withNoUser(), $this->user_helper, $this->retrieve_artifact);
        $this->setSemanticContributorWithUserCanRead(true);

        $this->expectException(\LogicException::class);
        $builder->getResult($select_results, $this->user);
    }

    public function testItThrowsWhenArtifactIsNotFound(): void
    {
        $other_user     = UserTestBuilder::aRandomActiveUser()->build();
        $select_results = [['id' => 999, '@assigned_to' => [$this->user->getId(), $other_user->getId()]]];
        $builder        = new AssignedToResultBuilder(RetrieveUserByIdStub::withUsers($this->user, $other_user), $this->user_helper, RetrieveArtifactStub::withNoArtifact());
        $this->setSemanticContributorWithUserCanRead(true);

        $this->expectException(\LogicException::class);
         $builder->getResult($select_results, $this->user);
    }

    public function testItDoesNothingWhenSemanticContributorIsNotSet(): void
    {
        $select_results = [['id' => 41, '@assigned_to' => $this->user->getId()]];
        $builder        = new AssignedToResultBuilder($this->user_retriever, $this->user_helper, $this->retrieve_artifact);
        TrackerSemanticContributor::setInstance($this->semantic_contributor, $this->artifact->getTracker());
        $this->semantic_contributor->method('getField')->willReturn(null);

        $collection = $builder->getResult($select_results, $this->user);
        self::assertEqualsCanonicalizing([
            $this->artifact->getId() => new SelectedValue('@assigned_to', new UserListRepresentation([])),
        ], $collection->values);
    }

    public function testItDoesNothingWhenUserCanNotReadField(): void
    {
        $select_results = [['id' => 41, '@assigned_to' => $this->user->getId()]];
        $builder        = new AssignedToResultBuilder($this->user_retriever, $this->user_helper, $this->retrieve_artifact);
        $this->setSemanticContributorWithUserCanRead(false);

        $collection = $builder->getResult($select_results, $this->user);
        self::assertEqualsCanonicalizing([
            $this->artifact->getId() => new SelectedValue('@assigned_to', new UserListRepresentation([])),
        ], $collection->values);
    }

    public function testItDoesNothingWhenAssignedToIsForNoneUser(): void
    {
        $select_results = [['id' => 41, '@assigned_to' => Tracker_FormElement_Field_List_Bind::NONE_VALUE]];
        $builder        = new AssignedToResultBuilder($this->user_retriever, $this->user_helper, $this->retrieve_artifact);
        $this->setSemanticContributorWithUserCanRead(true);

        $collection = $builder->getResult($select_results, $this->user);
        self::assertEqualsCanonicalizing([
            $this->artifact->getId() => new SelectedValue('@assigned_to', new UserListRepresentation([])),
        ], $collection->values);
    }

    public function testItBuildsAssignedToRepresentationForASingleUser(): void
    {
        $select_results = [['id' => 41, '@assigned_to' => $this->user->getId()]];
        $builder        = new AssignedToResultBuilder($this->user_retriever, $this->user_helper, $this->retrieve_artifact);
        $this->setSemanticContributorWithUserCanRead(true);

        $collection = $builder->getResult($select_results, $this->user);
        self::assertEqualsCanonicalizing([
            $this->artifact->getId() => new SelectedValue('@assigned_to', new UserListRepresentation([
                UserRepresentation::fromPFUser($this->user, $this->user_helper),
            ])),
        ], $collection->values);
    }

    public function testItBuildsAssignedToRepresentationWhenSeveralUsersAreSelected(): void
    {
        $other_user = UserTestBuilder::aRandomActiveUser()->withAvatarUrl('example.com/other/avatar/url')->build();

        $select_results = [['id' => $this->artifact->getId(), '@assigned_to' => [$this->user->getId(), $other_user->getId()]]];

        $builder = new AssignedToResultBuilder(RetrieveUserByIdStub::withUsers($this->user, $other_user), $this->user_helper, $this->retrieve_artifact);
        $this->setSemanticContributorWithUserCanRead(true);

        $collection = $builder->getResult($select_results, $this->user);
        self::assertEqualsCanonicalizing([
            $this->artifact->getId() => new SelectedValue('@assigned_to', new UserListRepresentation([
                UserRepresentation::fromPFUser($this->user, $this->user_helper),
                UserRepresentation::fromPFUser($other_user, $this->user_helper),
            ])),
        ], $collection->values);
    }

    private function setSemanticContributorWithUserCanRead(bool $user_can_read): void
    {
        TrackerSemanticContributor::setInstance($this->semantic_contributor, $this->artifact->getTracker());
        $this->assigned_to_field = SelectboxFieldBuilder::aSelectboxField(12)->withReadPermission($this->user, $user_can_read)->build();
        $this->semantic_contributor->method('getField')->willReturn($this->assigned_to_field);
    }
}
