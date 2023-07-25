<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;
use Tuleap\Tracker\FormElement\Field\File\IdForXMLImportExportConvertor;

class Tracker_Artifact_XMLImport_XMLImportFieldStrategyAttachment implements Tracker_Artifact_XMLImport_XMLImportFieldStrategy
{
    public const FILE_INFO_COPY_OPTION = 'is_migrated';
    public const FILE_INFO_MOVE_OPTION = 'is_moved';

    public function __construct(private readonly string $extraction_path, private readonly Tracker_Artifact_XMLImport_CollectionOfFilesToImportInArtifact $files_importer, private readonly \Psr\Log\LoggerInterface $logger)
    {
    }

    /**
     * Extract Field data from XML input
     *
     *
     * @return mixed
     * @throws Tracker_Artifact_XMLImport_Exception_NoValidAttachementsException
     */
    public function getFieldData(
        Tracker_FormElement_Field $field,
        SimpleXMLElement $field_change,
        PFUser $submitted_by,
        Artifact $artifact,
        PostCreationContext $context,
    ) {
        $values = $field_change->value;

        assert($field instanceof Tracker_FormElement_Field_File);

        $files_infos = [];

        if ($this->isFieldChangeEmpty($values)) {
            $this->logger->info(
                'Skipped attachment field ' . $field->getLabel() . ': field value is empty.'
            );

            return $files_infos;
        }

        foreach ($values as $value) {
            try {
                $attributes = $value->attributes();
                $file_id    = (string) ($attributes['ref'] ?? '');
                $file       = $this->files_importer->getFileXML($file_id);

                if (! $this->files_importer->fileIsAlreadyImported($file_id)) {
                    $files_infos[] = $this->getFileInfoForAttachment($file, $submitted_by, $context, $field);
                    $this->files_importer->markAsImported($file_id);
                }
            } catch (Tracker_Artifact_XMLImport_Exception_FileNotFoundException $exception) {
                $this->logger->warning('Skipped attachment field ' . $field->getLabel() . ': ' . $exception->getMessage());
            }
        }

        if ($this->itCannotImportAnyFiles($values, $files_infos)) {
            throw new Tracker_Artifact_XMLImport_Exception_NoValidAttachementsException();
        }

        return $files_infos;
    }

    private function isFieldChangeEmpty(SimpleXMLElement $values)
    {
        if (count($values) === 1) {
            $value      = $values[0];
            $attributes = $value->attributes();

            return (! isset($attributes['ref']));
        }

        return false;
    }

    private function itCannotImportAnyFiles($values, $files_infos)
    {
        return count($values) > 0 && count($files_infos) === 0;
    }

    private function getFileInfoForAttachment(SimpleXMLElement $file_xml, PFUser $submitted_by, ?PostCreationContext $context, Tracker_FormElement_Field_File $field)
    {
        $file_path = $this->extraction_path . '/' . (string) $file_xml->path;

        $fileinfo = [
            'submitted_by' => $submitted_by,
            'name' => (string) $file_xml->filename,
            'type' => (string) $file_xml->filetype,
            'description' => (string) $file_xml->description,
            'size' => (int) $file_xml->filesize,
            'tmp_name' => $file_path,
            'error' => UPLOAD_ERR_OK,
        ];

        if ($context?->getImportConfig()->getMoveImportConfig()->is_ducktyping_move) {
            $fileinfo[self::FILE_INFO_MOVE_OPTION] = true;
        } else {
            $fileinfo[self::FILE_INFO_COPY_OPTION] = true;
        }

        try {
            $attributes = $file_xml->attributes();
            if ($attributes) {
                $fileinfo_id                      = (string) $attributes['id'];
                $fileinfo['previous_fileinfo_id'] = IdForXMLImportExportConvertor::convertXMLIdToFileInfoId(
                    $fileinfo_id
                );

                $fileinfo['tmp_name'] = $this->getFileInfoTmpName($context, $field, $fileinfo);
            }
        } catch (InvalidArgumentException $exception) {
            // It seems that we don't know this xml id. Just ignore it.
        }

        if (! is_file($fileinfo['tmp_name'])) {
            throw new Tracker_Artifact_XMLImport_Exception_FileNotFoundException($fileinfo['tmp_name']);
        }

        return $fileinfo;
    }

    /**
     * @param \Tuleap\Tracker\Action\FieldMapping[] $mapping_field
     */
    private function findSourceFieldInFieldsMapping(
        array $mapping_field,
        int $destination_field_id,
    ): ?\Tracker_FormElement_Field {
        foreach ($mapping_field as $field) {
            if ($field->destination->getId() === $destination_field_id) {
                return $field->source;
            }
        }

        return null;
    }

    private function getFileInfoTmpName(?PostCreationContext $context, Tracker_FormElement_Field_File $field, array $fileinfo): string
    {
        if ($context?->getImportConfig()->getMoveImportConfig()->is_ducktyping_move) {
            $source_field = $this->findSourceFieldInFieldsMapping($context?->getImportConfig()->getMoveImportConfig()->field_mapping, $field->getId());
            if (! $source_field) {
                throw new Tracker_FormElement_InvalidFieldException();
            }
            return ForgeConfig::get('sys_data_dir') . '/tracker/' . $source_field->getId() . "/" . $fileinfo['previous_fileinfo_id'];
        }
        return $fileinfo['tmp_name'];
    }
}
