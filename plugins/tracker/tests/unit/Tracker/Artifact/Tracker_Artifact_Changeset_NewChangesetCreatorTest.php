<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\Tracker\Artifact;

use Mockery;
use PFUser;
use Tracker_AfterSaveException;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_Changeset_ChangesetDataInitializator;
use Tracker_Artifact_Changeset_Comment;
use Tracker_Artifact_Changeset_NewChangesetCreator;
use Tracker_Artifact_Changeset_Null;
use Tuleap\Tracker\Artifact\Changeset\ArtifactChangesetSaver;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionInserter;
use Tuleap\Tracker\Artifact\Changeset\FieldsToBeSavedInSpecificOrderRetriever;
use Tuleap\Tracker\Artifact\XMLImport\TrackerNoXMLImportLoggedConfig;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ParentLinkAction;

final class Tracker_Artifact_Changeset_NewChangesetCreatorTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ArtifactChangesetSaver
     */
    private $changeset_saver;

    /**
     * @var mixed
     */
    private $submitted_on;
    /**
     * @var Mockery\Mock | \Tuleap\Tracker\Artifact\Artifact
     */
    private $artifact;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Workflow
     */
    private $workflow;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var PFUser
     */
    private $submitter;
    /**
     * @var array
     */
    private $fields_data;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_Artifact_ChangesetDao
     */
    private $changeset_dao;
    /**
     * @var Tracker_Artifact_Changeset_NewChangesetCreator
     */
    private $creator;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ParentLinkAction
     */
    private $parent_link_action;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerPrivateCommentUGroupPermissionInserter
     */
    private $ugroup_private_comment_inserter;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Tracker_Artifact_Changeset_CommentDao
     */
    private $comment_dao;

    protected function setUp(): void
    {
        $this->fields_data = [];
        $this->submitter   = new PFUser(['user_id' => 74, 'language_id' => 'en']);

        $this->changeset_dao = \Mockery::spy(\Tracker_Artifact_ChangesetDao::class);
        $changeset           = new Tracker_Artifact_Changeset_Null();
        $factory             = \Mockery::spy(\Tracker_FormElementFactory::class);
        $factory->shouldReceive('getAllFormElementsForTracker')->andReturn([]);
        $factory->shouldReceive('getUsedFields')->andReturn([]);

        $this->artifact_factory = \Mockery::spy(\Tracker_ArtifactFactory::class);
        $this->workflow         = \Mockery::spy(\Workflow::class);

        $tracker = Mockery::spy(\Tracker::class);
        $tracker->shouldReceive('getWorkflow')->andReturn($this->workflow);
        $tracker->shouldReceive('getProject')->andReturn(new \Project(['group_id' => 101]));
        $this->artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->artifact->shouldReceive('getLastChangeset')->andReturn($changeset);
        $this->artifact->shouldReceive('getWorkflow')->andReturn($this->workflow);
        $this->artifact->shouldReceive('getTracker')->andReturn($tracker);

        $this->submitted_on = $_SERVER['REQUEST_TIME'];

        $fields_validator = \Mockery::spy(\Tracker_Artifact_Changeset_NewChangesetFieldsValidator::class);
        $fields_validator->shouldReceive('validate')->andReturn(true);

        $this->comment_dao = Mockery::mock(\Tracker_Artifact_Changeset_CommentDao::class);
        $this->comment_dao->shouldReceive('createNewVersion')->andReturn(true)->byDefault();

        $this->parent_link_action = Mockery::mock(ParentLinkAction::class);

        $field_initializator = Mockery::mock(Tracker_Artifact_Changeset_ChangesetDataInitializator::class);
        $field_initializator->shouldReceive('process')->andReturn([]);
        $field_retriever = Mockery::mock(FieldsToBeSavedInSpecificOrderRetriever::class);
        $field_retriever->shouldReceive('getFields')->andReturn([]);
        $this->changeset_saver = Mockery::mock(ArtifactChangesetSaver::class);

        $this->ugroup_private_comment_inserter = Mockery::mock(TrackerPrivateCommentUGroupPermissionInserter::class);

        $this->creator = new Tracker_Artifact_Changeset_NewChangesetCreator(
            $fields_validator,
            $field_retriever,
            $this->changeset_dao,
            $this->comment_dao,
            $this->artifact_factory,
            \Mockery::spy(\EventManager::class),
            \Mockery::spy(\ReferenceManager::class),
            $field_initializator,
            new \Tuleap\Test\DB\DBTransactionExecutorPassthrough(),
            $this->changeset_saver,
            $this->parent_link_action,
            $this->ugroup_private_comment_inserter
        );
    }

    public function testItCallsTheAfterMethodOnWorkflowWhenCreateNewChangeset(): void
    {
        $this->changeset_dao->shouldReceive('create')->andReturn(true);
        $this->artifact_factory->shouldReceive('save')->andReturn(true);
        $this->workflow->shouldReceive('after')->once();
        $this->changeset_saver->shouldReceive('saveChangeset')->once();

        $this->creator->create(
            $this->artifact,
            $this->fields_data,
            '',
            $this->submitter,
            $this->submitted_on,
            false,
            Tracker_Artifact_Changeset_Comment::TEXT_COMMENT,
            Mockery::mock(\Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping::class),
            new TrackerNoXMLImportLoggedConfig(),
            []
        );
    }

    public function testItCallsTheParentLinkActionIfNoChangesDetected(): void
    {
        $changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('hasChanges')->andReturnFalse();

        $artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive('getLastChangeset')->andReturn($changeset);
        $artifact->shouldReceive('getId')->andReturn(154);
        $artifact->shouldReceive('getXRef')->andReturn('xRef');

        $this->changeset_dao->shouldNotReceive('create');
        $this->artifact_factory->shouldNotReceive('save');
        $this->workflow->shouldNotReceive('after');
        $this->changeset_saver->shouldNotReceive('saveChangeset');

        $this->parent_link_action->shouldReceive('linkParent')->once()->andReturnTrue();

        $this->creator->create(
            $artifact,
            $this->fields_data,
            '',
            $this->submitter,
            $this->submitted_on,
            false,
            Tracker_Artifact_Changeset_Comment::TEXT_COMMENT,
            Mockery::mock(\Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping::class),
            new TrackerNoXMLImportLoggedConfig(),
            []
        );
    }

    public function testItDoesNotCallTheAfterMethodOnWorkflowWhenSaveOfArtifactFailsOnNewChangeset(): void
    {
        $this->changeset_dao->shouldReceive('create')->andReturn(true);
        $this->artifact_factory->shouldReceive('save')->andReturn(false);
        $this->workflow->shouldReceive('after')->never();
        $this->changeset_saver->shouldReceive('saveChangeset')->once();

        $this->expectException(Tracker_AfterSaveException::class);

        $this->creator->create(
            $this->artifact,
            $this->fields_data,
            '',
            $this->submitter,
            $this->submitted_on,
            false,
            Tracker_Artifact_Changeset_Comment::TEXT_COMMENT,
            Mockery::mock(\Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping::class),
            new TrackerNoXMLImportLoggedConfig(),
            []
        );
    }

    public function testItSavesUgroupPrivateComment(): void
    {
        $ugroup = Mockery::mock(\ProjectUGroup::class);
        $this->comment_dao->shouldReceive('createNewVersion')->once()->andReturn(15);

        $this->ugroup_private_comment_inserter
            ->shouldReceive('insertUGroupsOnPrivateComment')
            ->with(15, [$ugroup])
            ->once();

        $this->changeset_dao->shouldReceive('create')->andReturn(true);
        $this->artifact_factory->shouldReceive('save')->andReturn(true);
        $this->workflow->shouldReceive('after')->once();
        $this->changeset_saver->shouldReceive('saveChangeset')->once();

        $this->creator->create(
            $this->artifact,
            $this->fields_data,
            '',
            $this->submitter,
            $this->submitted_on,
            false,
            Tracker_Artifact_Changeset_Comment::TEXT_COMMENT,
            Mockery::mock(\Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping::class),
            new TrackerNoXMLImportLoggedConfig(),
            [$ugroup]
        );
    }
}
