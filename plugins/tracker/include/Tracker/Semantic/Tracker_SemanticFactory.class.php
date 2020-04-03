<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\Tracker\Semantic\IBuildSemanticFromXML;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDuplicator;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeFromXMLBuilder;

class Tracker_SemanticFactory
{

    /**
     * Hold an instance of the class
     */
    protected static $instance;

    /**
     * The singleton method
     *
     * @return Tracker_SemanticFactory an instance of the factory
     */
    public static function instance()
    {
        if (!isset(self::$instance)) {
            $c = self::class;
            self::$instance = new $c();
        }
        return self::$instance;
    }

    public function getInstanceFromXML(
        SimpleXMLElement $xml,
        SimpleXMLElement $full_semantic_xml,
        array $xml_mapping,
        Tracker $tracker
    ): ?Tracker_Semantic {
        $semantic = null;
        $attributes = $xml->attributes();
        $type = $attributes['type'];

        $builder = $this->getSemanticFromXMLBuilder((string) $type);
        if ($builder === null) {
            return $this->getSemanticFromAnotherPlugin($xml, $full_semantic_xml, $xml_mapping, $tracker, $type);
        }

        return $builder->getInstanceFromXML($xml, $xml_mapping, $tracker);
    }

    private function getSemanticFromXMLBuilder(string $type): ?IBuildSemanticFromXML
    {
        if ($type === 'title') {
            return $this->getSemanticTitleFactory();
        }

        if ($type === 'description') {
            return $this->getSemanticDescriptionFactory();
        }

        if ($type === 'status') {
            return $this->getSemanticStatusFactory();
        }

        if ($type === 'contributor') {
            return $this->getSemanticContributorFactory();
        }

        if ($type === 'tooltip') {
            return $this->getSemanticTooltipFactory();
        }

        if ($type === 'timeframe') {
            return (new SemanticTimeframeFromXMLBuilder());
        }

        return null;
    }

    private function getSemanticFromAnotherPlugin(
        SimpleXMLElement $xml,
        SimpleXMLElement $full_semantic_xml,
        array $xml_mapping,
        Tracker $tracker,
        $type
    ) {
        $semantic = null;

        EventManager::instance()->processEvent(
            TRACKER_EVENT_SEMANTIC_FROM_XML,
            array(
                'xml'               => $xml,
                'full_semantic_xml' => $full_semantic_xml,
                'xml_mapping'       => $xml_mapping,
                'tracker'           => $tracker,
                'semantic'          => &$semantic,
                'type'              => $type,
            )
        );

        return $semantic;
    }


    /**
     * Returns an instance of Tracker_Semantic_TitleFactory
     *
     * @return Tracker_Semantic_TitleFactory an instance of the factory
     */
    public function getSemanticTitleFactory()
    {
        return Tracker_Semantic_TitleFactory::instance();
    }

    /**
     * Returns an instance of Tracker_Semantic_TitleFactory
     *
     * @return Tracker_Semantic_TitleFactory an instance of the factory
     */
    public function getSemanticDescriptionFactory()
    {
        return Tracker_Semantic_DescriptionFactory::instance();
    }

    /**
     * Returns an instance of Tracker_Semantic_StatusFactory
     *
     * @return Tracker_Semantic_StatusFactory an instance of the factory
     */
    public function getSemanticStatusFactory()
    {
        return Tracker_Semantic_StatusFactory::instance();
    }
    /**
     * Returns an instance of Tracker_TooltipFactory
     *
     * @return Tracker_TooltipFactory an instance of the factory
     */
    public function getSemanticTooltipFactory()
    {
        return Tracker_TooltipFactory::instance();
    }

    /**
     * Returns an instance of Tracker_ContributorFactory
     *
     * @return Tracker_Semantic_ContributorFactory an instance of the factory
     */
    public function getSemanticContributorFactory()
    {
        return Tracker_Semantic_ContributorFactory::instance();
    }

    /**
     * Creates new Tracker_Semantic in the database
     *
     * @param Tracker_Semantic $semantic The semantic to save
     * @param Tracker          $tracker  The tracker
     *
     * @return bool true if the semantic is saved, false otherwise
     */
    public function saveObject($semantic, $tracker)
    {
        $semantic->setTracker($tracker);
        return $semantic->save();
    }

    /**
     * Duplicate the semantics from tracker source to tracker target
     *
     * @param int   $from_tracker_id The Id of the tracker source
     * @param int   $to_tracker_id   The Id of the tracker target
     * @param array $field_mapping   The mapping of the fields of the tracker
     *
     * @return void
     */
    public function duplicate($from_tracker_id, $to_tracker_id, $field_mapping)
    {
        foreach ($this->getDuplicators() as $duplicator) {
            $duplicator->duplicate($from_tracker_id, $to_tracker_id, $field_mapping);
        }
    }

    /** @return \Tuleap\Tracker\Semantic\IDuplicateSemantic[] */
    private function getDuplicators()
    {
        $timeframe_duplicator = new SemanticTimeframeDuplicator(
            new SemanticTimeframeDao()
        );

        $duplicators = array(
            $this->getSemanticTitleFactory(),
            $this->getSemanticDescriptionFactory(),
            $this->getSemanticStatusFactory(),
            $this->getSemanticContributorFactory(),
            $this->getSemanticTooltipFactory(),
            $timeframe_duplicator
        );

        EventManager::instance()->processEvent(
            TRACKER_EVENT_GET_SEMANTIC_DUPLICATORS,
            array('duplicators' => &$duplicators)
        );

        return $duplicators;
    }
}
