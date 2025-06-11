<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

namespace Tuleap\Tracker\Semantic\Contributor;

use SimpleXMLElement;
use Tracker;
use Tracker_Semantic;
use Tuleap\Tracker\Semantic\IBuildSemanticFromXML;
use Tuleap\Tracker\Semantic\IDuplicateSemantic;

class TrackerSemanticContributorFactory implements IBuildSemanticFromXML, IDuplicateSemantic
{
    /**
     * Hold an instance of the class
     * @var self|null
     */
    protected static $instance;

    /**
     * The singleton method
     *
     * @return TrackerSemanticContributorFactory an instance of the factory
     */
    public static function instance()
    {
        if (! isset(self::$instance)) {
            $c              = self::class;
            self::$instance = new $c();
        }
        return self::$instance;
    }

    public function getByTracker(Tracker $tracker)
    {
        return TrackerSemanticContributor::load($tracker);
    }

    public function getInstanceFromXML(
        SimpleXMLElement $current_semantic_xml,
        SimpleXMLElement $all_semantics_xml,
        array $xml_mapping,
        Tracker $tracker,
        array $tracker_mapping,
    ): ?Tracker_Semantic {
        $xml_field            = $current_semantic_xml->field;
        $xml_field_attributes = $xml_field->attributes();
        if (! isset($xml_mapping[(string) $xml_field_attributes['REF']])) {
            return null;
        }
        $field = $xml_mapping[(string) $xml_field_attributes['REF']];
        return new TrackerSemanticContributor($tracker, $field);
    }

    /**
     * Return the Dao
     *
     * @return TrackerSemanticContributorDao The dao
     */
    public function getDao()
    {
        return new TrackerSemanticContributorDao();
    }

    /**
     * Duplicate the semantic from tracker source to tracker target
     */
    public function duplicate(int $from_tracker_id, int $to_tracker_id, array $field_mapping): void
    {
        $row = $this->getDao()->searchByTrackerId($from_tracker_id)->getRow();
        if ($row) {
            $from_contributor_field_id = $row['field_id'];
            $to_contributor_field_id   = false;
            foreach ($field_mapping as $mapping) {
                if ($mapping['from'] == $from_contributor_field_id) {
                    $to_contributor_field_id = $mapping['to'];
                }
            }
            if ($to_contributor_field_id) {
                $this->getDao()->save($to_tracker_id, $to_contributor_field_id);
            }
        }
    }
}
