<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\File;

use Tracker_Artifact;
use Tracker_Artifact_Attachment_AlreadyLinkedToAnotherArtifactException;
use Tracker_Artifact_Attachment_FileNotFoundException;
use Tracker_Artifact_Attachment_TemporaryFileManager;
use Tracker_FileInfoFactory;
use Tracker_FormElementFactory;
use UserManager;

class FieldDataFromRESTBuilder
{
    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var Tracker_FileInfoFactory
     */
    private $file_info_factory;
    /**
     * @var Tracker_Artifact_Attachment_TemporaryFileManager
     */
    private $temporary_file_manager;
    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct(
        UserManager $user_manager,
        Tracker_FormElementFactory $form_element_factory,
        Tracker_FileInfoFactory $file_info_factory,
        Tracker_Artifact_Attachment_TemporaryFileManager $temporary_file_manager
    ) {
        $this->user_manager           = $user_manager;
        $this->form_element_factory   = $form_element_factory;
        $this->file_info_factory      = $file_info_factory;
        $this->temporary_file_manager = $temporary_file_manager;
    }

    /**
     * Get the field data for artifact submission
     * @throws Tracker_Artifact_Attachment_FileNotFoundException
     * @throws Tracker_Artifact_Attachment_AlreadyLinkedToAnotherArtifactException
     */
    public function buildFieldDataFromREST($rest_value, ?Tracker_Artifact $artifact)
    {
        $field_data = [];

        $submitted_ids = $rest_value->value;
        foreach ($submitted_ids as $file_id) {
            $file_id = (int) $file_id;

            $linked_artifact = $this->file_info_factory->getArtifactByFileInfoIdInLastChangeset($file_id);
            if ($linked_artifact) {
                $this->checkLinkedArtifactIsLinkedToTheCurrentOne($linked_artifact, $artifact, $file_id);
                continue;
            }

            $field_data[] = $this->getFileInfoDataForTemporaryFile($file_id);
        }


        if ($artifact) {
            $this->markAsDeletedPreviouslyAttachedFilesThatAreNotSubmitted(
                $artifact,
                $submitted_ids,
                $field_data
            );
        }

        return $field_data;
    }

    /**
     * @throws Tracker_Artifact_Attachment_AlreadyLinkedToAnotherArtifactException
     */
    private function checkLinkedArtifactIsLinkedToTheCurrentOne(
        Tracker_Artifact $linked_artifact,
        ?Tracker_Artifact $current_artifact,
        int $file_id
    ): void {
        if (! $current_artifact) {
            throw new Tracker_Artifact_Attachment_AlreadyLinkedToAnotherArtifactException(
                $file_id,
                $linked_artifact
            );
        }

        if ((int) $current_artifact->getId() !== (int) $linked_artifact->getId()) {
            throw new Tracker_Artifact_Attachment_AlreadyLinkedToAnotherArtifactException(
                $file_id,
                $linked_artifact
            );
        }
    }

    /**
     * @param int $file_id
     *
     * @return array
     * @throws Tracker_Artifact_Attachment_FileNotFoundException
     */
    private function getFileInfoDataForTemporaryFile(int $file_id): array
    {
        $this->checkFileIsTemporary($file_id);

        $temporary_file = $this->temporary_file_manager->getFile($file_id);

        $user = $this->user_manager->getUserById($temporary_file->getCreatorId());
        if (! $user || ! $this->temporary_file_manager->exists($user, $temporary_file->getTemporaryName())) {
            throw new Tracker_Artifact_Attachment_FileNotFoundException(
                'Temporary file #' . $file_id . ' not found'
            );
        }

        $file_info_data = $this->file_info_factory->buildFileInfoData(
            $temporary_file,
            $this->temporary_file_manager->getPath($user, $temporary_file->getTemporaryName())
        );

        return $file_info_data;
    }

    /**
     * @throws Tracker_Artifact_Attachment_FileNotFoundException
     */
    private function checkFileIsTemporary(int $file_id): void
    {
        if (! $this->temporary_file_manager->isFileIdTemporary($file_id)) {
            throw new Tracker_Artifact_Attachment_FileNotFoundException(
                'Temporary file #' . $file_id . ' not found'
            );
        }
    }

    private function markAsDeletedPreviouslyAttachedFilesThatAreNotSubmitted(
        Tracker_Artifact $artifact,
        array $submitted_ids,
        array &$field_data
    ): void {
        foreach ($this->getAlreadyAttachedFileIds($artifact) as $file_id) {
            // Not in given ids => unlink
            if (! in_array($file_id, $submitted_ids)) {
                $field_data['delete'][] = $file_id;
            }
        }
    }

    /**
     * @return int[]
     */
    private function getAlreadyAttachedFileIds(Tracker_Artifact $artifact): array
    {
        $formelement_files = $this->form_element_factory->getUsedFormElementsByType($artifact->getTracker(), 'file');

        $last_changeset_file_ids = [];

        foreach ($formelement_files as $field) {
            assert($field instanceof \Tracker_FormElement_Field_File);
            $value = $field->getLastChangesetValue($artifact);

            if ($value) {
                foreach ($value->getFiles() as $file) {
                    $last_changeset_file_ids[] = (int) $file->getId();
                }
            }
        }

        return $last_changeset_file_ids;
    }
}
