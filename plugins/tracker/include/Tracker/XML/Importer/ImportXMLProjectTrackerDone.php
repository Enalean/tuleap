<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\XML\Importer;

use Project;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tracker_XML_Importer_ArtifactImportedMapping;
use TrackerXmlFieldsMapping;
use Tuleap\Event\Dispatchable;
use Tuleap\XML\MappingsRegistry;
use User\XML\Import\IFindUserFromXMLReference;

class ImportXMLProjectTrackerDone implements Dispatchable
{
    public const NAME = 'importXMLProjectTrackerDone';

    /**
     * @var Project
     */
    private $project;

    /**
     * @var SimpleXMLElement
     */
    private $xml_element;

    /**
     * @var array
     */
    private $created_trackers_mapping;

    /**
     * @var array
     */
    private $xml_fields_mapping;

    /**
     * @var MappingsRegistry
     */
    private $mappings_registery;

    /**
     * @var Tracker_XML_Importer_ArtifactImportedMapping
     */
    private $artifacts_id_mapping;

    /**
     * @var ImportedChangesetMapping
     */
    private $changeset_id_mapping;

    /**
     * @var string
     */
    private $extraction_path;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TrackerXmlFieldsMapping
     */
    private $xml_field_values_mapping;

    /**
     * @var IFindUserFromXMLReference
     */
    private $user_finder;

    /**
     * @var array
     */
    private $created_trackers_objects;

    private \PFUser $user;

    public function __construct(
        Project $project,
        SimpleXMLElement $xml_element,
        array $created_trackers_mapping,
        array $xml_fields_mapping,
        MappingsRegistry $mappings_registery,
        Tracker_XML_Importer_ArtifactImportedMapping $artifacts_id_mapping,
        ImportedChangesetMapping $changeset_id_mapping,
        string $extraction_path,
        LoggerInterface $logger,
        TrackerXmlFieldsMapping $xml_field_values_mapping,
        IFindUserFromXMLReference $user_finder,
        array $created_trackers_objects,
        \PFUser $user,
    ) {
        $this->project                  = $project;
        $this->xml_element              = $xml_element;
        $this->created_trackers_mapping = $created_trackers_mapping;
        $this->xml_fields_mapping       = $xml_fields_mapping;
        $this->mappings_registery       = $mappings_registery;
        $this->artifacts_id_mapping     = $artifacts_id_mapping;
        $this->changeset_id_mapping     = $changeset_id_mapping;
        $this->extraction_path          = $extraction_path;
        $this->logger                   = $logger;
        $this->xml_field_values_mapping = $xml_field_values_mapping;
        $this->user_finder              = $user_finder;
        $this->created_trackers_objects = $created_trackers_objects;
        $this->user                     = $user;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getXmlElement(): SimpleXMLElement
    {
        return $this->xml_element;
    }

    public function getCreatedTrackersMapping(): array
    {
        return $this->created_trackers_mapping;
    }

    public function getXmlFieldsMapping(): array
    {
        return $this->xml_fields_mapping;
    }

    public function getMappingsRegistery(): MappingsRegistry
    {
        return $this->mappings_registery;
    }

    public function getArtifactsIdMapping(): Tracker_XML_Importer_ArtifactImportedMapping
    {
        return $this->artifacts_id_mapping;
    }

    public function getChangesetIdMapping(): ImportedChangesetMapping
    {
        return $this->changeset_id_mapping;
    }

    public function getExtractionPath(): string
    {
        return $this->extraction_path;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function getXmlFieldValuesMapping(): TrackerXmlFieldsMapping
    {
        return $this->xml_field_values_mapping;
    }

    public function getUserFinder(): IFindUserFromXMLReference
    {
        return $this->user_finder;
    }

    public function getCreatedTrackersObjects(): array
    {
        return $this->created_trackers_objects;
    }

    public function getUser(): \PFUser
    {
        return $this->user;
    }
}
