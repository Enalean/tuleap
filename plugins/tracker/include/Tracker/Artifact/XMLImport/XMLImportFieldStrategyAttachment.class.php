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

use Tuleap\Tracker\FormElement\Field\File\IdForXMLImportExportConvertor;

class Tracker_Artifact_XMLImport_XMLImportFieldStrategyAttachment implements Tracker_Artifact_XMLImport_XMLImportFieldStrategy
{


    public const FILE_INFO_COPY_OPTION = 'is_migrated';

    /** @var string */
    private $extraction_path;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /** @var Tracker_Artifact_XMLImport_CollectionOfFilesToImportInArtifact */
    private $files_importer;

    public function __construct($extraction_path, Tracker_Artifact_XMLImport_CollectionOfFilesToImportInArtifact $files_importer, \Psr\Log\LoggerInterface $logger)
    {
        $this->extraction_path = $extraction_path;
        $this->files_importer  = $files_importer;
        $this->logger          = $logger;
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
        Tracker_Artifact $artifact
    ) {
        $values      = $field_change->value;

        $files_infos = array();

        if ($this->isFieldChangeEmpty($values)) {
            $this->logger->warning(
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
                    $files_infos[] = $this->getFileInfoForAttachment($file, $submitted_by);
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

    private function getFileInfoForAttachment(SimpleXMLElement $file_xml, PFUser $submitted_by)
    {
        $file_path =  $this->extraction_path . '/' . (string) $file_xml->path;
        if (! is_file($file_path)) {
            throw new Tracker_Artifact_XMLImport_Exception_FileNotFoundException($file_path);
        }
        $fileinfo = [
            self::FILE_INFO_COPY_OPTION => true,
            'submitted_by'              => $submitted_by,
            'name'                      => (string) $file_xml->filename,
            'type'                      => (string) $file_xml->filetype,
            'description'               => (string) $file_xml->description,
            'size'                      => (int) $file_xml->filesize,
            'tmp_name'                  => $file_path,
            'error'                     => UPLOAD_ERR_OK,
        ];

        try {
            $attributes = $file_xml->attributes();
            if ($attributes) {
                $fileinfo_id = (string) $attributes['id'];
                $fileinfo['previous_fileinfo_id'] = IdForXMLImportExportConvertor::convertXMLIdToFileInfoId(
                    $fileinfo_id
                );
            }
        } catch (InvalidArgumentException $exception) {
            // It seems that we don't know this xml id. Just ignore it.
        }

        return $fileinfo;
    }
}
