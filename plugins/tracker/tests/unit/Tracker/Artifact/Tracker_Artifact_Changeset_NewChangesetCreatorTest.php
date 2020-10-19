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
use PHPUnit\Framework\TestCase;
use Tracker_AfterSaveException;
use Tracker_Artifact_Changeset_ChangesetDataInitializator;
use Tracker_Artifact_Changeset_Comment;
use Tracker_Artifact_Changeset_NewChangesetCreator;
use Tracker_Artifact_Changeset_Null;
use Tuleap\Tracker\Artifact\Changeset\ArtifactChangesetSaver;
use Tuleap\Tracker\Artifact\Changeset\FieldsToBeSavedInSpecificOrderRetriever;
use Tuleap\Tracker\Artifact\XMLImport\TrackerNoXMLImportLoggedConfig;

final class Tracker_Artifact_Changeset_NewChangesetCreatorTest extends TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
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
        $this->artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->artifact->shouldReceive('getLastChangeset')->andReturn($changeset);
        $this->artifact->shouldReceive('getWorkflow')->andReturn($this->workflow);
        $this->artifact->shouldReceive('getTracker')->andReturn($tracker);

        $this->submitted_on = $_SERVER['REQUEST_TIME'];

        $fields_validator = \Mockery::spy(\Tracker_Artifact_Changeset_NewChangesetFieldsValidator::class);
        $fields_validator->shouldReceive('validate')->andReturn(true);

        $comment_dao = Mockery::mock(\Tracker_Artifact_Changeset_CommentDao::class);
        $comment_dao->shouldReceive('createNewVersion')->andReturn(true);

        $field_initializator = Mockery::mock(Tracker_Artifact_Changeset_ChangesetDataInitializator::class);
        $field_initializator->shouldReceive('process')->andReturn([]);
        $field_retriever = Mockery::mock(FieldsToBeSavedInSpecificOrderRetriever::class);
        $field_retriever->shouldReceive('getFields')->andReturn([]);
        $this->changeset_saver          = Mockery::mock(ArtifactChangesetSaver::class);
        $this->creator = new Tracker_Artifact_Changeset_NewChangesetCreator(
            $fields_validator,
            $field_retriever,
            $this->changeset_dao,
            $comment_dao,
            $this->artifact_factory,
            \Mockery::spy(\EventManager::class),
            \Mockery::spy(\ReferenceManager::class),
            \Mockery::spy(\Tuleap\Tracker\FormElement\Field\ArtifactLink\SourceOfAssociationCollectionBuilder::class),
            $field_initializator,
            new \Tuleap\Test\DB\DBTransactionExecutorPassthrough(),
            $this->changeset_saver
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
            new TrackerNoXMLImportLoggedConfig()
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
            new TrackerNoXMLImportLoggedConfig()
        );
    }
}
