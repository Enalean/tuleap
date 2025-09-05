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

namespace Tuleap\Tracker\Semantic\Tooltip;

use Tuleap\Tracker\Semantic\CollectionOfFieldsDuplicator;
use Tuleap\Tracker\Semantic\TrackerSemantic;
use Tuleap\Tracker\Semantic\XML\IBuildSemanticFromXML;
use Tuleap\Tracker\Tracker;

class SemanticTooltipFactory implements IBuildSemanticFromXML
{
    /**
     * Hold an instance of the class
     *
     * @var self|null
     */
    protected static $instance;

    /**
     * The singleton method
     *
     * @return SemanticTooltipFactory an instance of this factory
     */
    public static function instance()
    {
        if (! isset(self::$instance)) {
            $c              = self::class;
            self::$instance = new $c();
        }
        return self::$instance;
    }

    /**
     * Get an instance of Tracker_Tooltip instance form a row
     *
     * @param array   $row     The row allowing the construction of a tooltip
     * @param Tracker $tracker The tracker
     *
     * @return SemanticTooltip
     */
    public function getInstanceFromRow($row, $tracker)
    {
        $tooltip = new SemanticTooltip($tracker);
        $tooltip->setFields($row);
        return $tooltip;
    }

    #[\Override]
    public function getInstanceFromXML(
        \SimpleXMLElement $current_semantic_xml,
        \SimpleXMLElement $all_semantics_xml,
        array $xml_mapping,
        Tracker $tracker,
        array $tracker_mapping,
    ): TrackerSemantic {
        $row = [];
        foreach ($current_semantic_xml->field as $field) {
            $att = $field->attributes();
            if (! isset($xml_mapping[(string) $att['REF']])) {
                continue;
            }
            $row[] = $xml_mapping[(string) $att['REF']];
        }

        return $this->getInstanceFromRow($row, $tracker);
    }

    /**
     * Get the dao
     *
     * @return SemanticTooltipDao The dao
     */
    public function getDao()
    {
        return new SemanticTooltipDao();
    }

    /**
     * Duplicate the semantic from tracker source to tracker target
     *
     * @return void
     */
    public function duplicate(int $from_tracker_id, int $to_tracker_id, array $field_mapping)
    {
        $duplicator = new CollectionOfFieldsDuplicator($this->getDao());
        $duplicator->duplicate($from_tracker_id, $to_tracker_id, $field_mapping);
    }
}
