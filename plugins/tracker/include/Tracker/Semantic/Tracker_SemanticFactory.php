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

use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkFieldValueDao;
use Tuleap\Tracker\Semantic\IBuildSemanticFromXML;
use Tuleap\Tracker\Semantic\Progress\SemanticProgressDao;
use Tuleap\Tracker\Semantic\Progress\SemanticProgressDuplicator;
use Tuleap\Tracker\Semantic\Progress\SemanticProgress;
use Tuleap\Tracker\Semantic\Progress\SemanticProgressFromXMLBuilder;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDone;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneDao;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneDuplicator;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneFactory;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneValueChecker;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeFromXMLBuilder;
use Tuleap\Tracker\Semantic\Tooltip\SemanticTooltip;
use Tuleap\Tracker\Semantic\Tooltip\SemanticTooltipFactory;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_SemanticFactory
{
    /**
     * Create a semantic from xml in other plugins
     *
     * Parameters:
     * 'xml'           => @var SimpleXMLElement
     * 'xml_mapping'   => @var array
     * 'tracker'       => @var Tracker
     * 'semantic'      => @var array
     * 'type'          => @var string
     *
     * Expected results
     * The semantic parameter is populated with a Tracker_Semantic object if it exists for the given type
     */
    public final const TRACKER_EVENT_SEMANTIC_FROM_XML = 'tracker_event_semantic_from_xml';

    /**
     * Get the various duplicators that can duplicate semantics
     *
     * Parameters:
     *  'duplicators' => \Tuleap\Tracker\Semantic\IDuplicateSemantic[]
     */
    public final const TRACKER_EVENT_GET_SEMANTIC_DUPLICATORS = 'tracker_event_get_semantic_duplicators';

    /**
     * Hold an instance of the class
     * @var self|null
     */
    protected static $instance;

    /**
     * The singleton method
     *
     * @return Tracker_SemanticFactory an instance of the factory
     */
    public static function instance()
    {
        if (! isset(self::$instance)) {
            $c              = self::class;
            self::$instance = new $c();
        }
        return self::$instance;
    }

    public function getInstanceFromXML(
        SimpleXMLElement $xml,
        SimpleXMLElement $full_semantic_xml,
        array $xml_mapping,
        Tracker $tracker,
        array $created_trackers_mapping,
    ): ?Tracker_Semantic {
        $attributes = $xml->attributes();
        $type       = $attributes['type'];

        $builder = $this->getSemanticFromXMLBuilder((string) $type);
        if ($builder === null) {
            return $this->getSemanticFromAnotherPlugin($xml, $full_semantic_xml, $xml_mapping, $tracker, $type);
        }

        return $builder->getInstanceFromXML($xml, $full_semantic_xml, $xml_mapping, $tracker, $created_trackers_mapping);
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

        if ($type === SemanticDone::NAME) {
            return $this->getSemanticDoneFactory();
        }

        if ($type === 'contributor') {
            return $this->getSemanticContributorFactory();
        }

        if ($type === SemanticTooltip::NAME) {
            return $this->getSemanticTooltipFactory();
        }

        if ($type === 'timeframe') {
            return (new SemanticTimeframeFromXMLBuilder(
                new ArtifactLinkFieldValueDao(),
                TrackerFactory::instance(),
                SemanticTimeframeBuilder::build()
            ));
        }

        if ($type === SemanticProgress::NAME) {
            return new SemanticProgressFromXMLBuilder(
                new SemanticProgressDao()
            );
        }

        return null;
    }

    private function getSemanticFromAnotherPlugin(
        SimpleXMLElement $xml,
        SimpleXMLElement $full_semantic_xml,
        array $xml_mapping,
        Tracker $tracker,
        $type,
    ) {
        $semantic = null;

        EventManager::instance()->processEvent(
            self::TRACKER_EVENT_SEMANTIC_FROM_XML,
            [
                'xml'               => $xml,
                'full_semantic_xml' => $full_semantic_xml,
                'xml_mapping'       => $xml_mapping,
                'tracker'           => $tracker,
                'semantic'          => &$semantic,
                'type'              => $type,
            ]
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
     * @return Tracker_Semantic_DescriptionFactory an instance of the factory
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
     * @return SemanticTooltipFactory an instance of the factory
     */
    public function getSemanticTooltipFactory()
    {
        return SemanticTooltipFactory::instance();
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

    private function getSemanticDoneFactory(): SemanticDoneFactory
    {
        return new SemanticDoneFactory(
            new SemanticDoneDao(),
            new SemanticDoneValueChecker()
        );
    }

    /**
     * Creates new Tracker_Semantic in the database
     *
     * @param Tracker_Semantic $semantic The semantic to save
     * @param Tracker          $tracker  The tracker
     */
    public function saveObject($semantic, $tracker): void
    {
        $semantic->setTracker($tracker);
        $semantic->save();
    }

    /**
     * Duplicate the semantics from tracker source to tracker target
     *
     * @return void
     */
    public function duplicate(int $from_tracker_id, int $to_tracker_id, array $field_mapping)
    {
        foreach ($this->getDuplicators() as $duplicator) {
            $duplicator->duplicate($from_tracker_id, $to_tracker_id, $field_mapping);
        }
    }

    /** @return \Tuleap\Tracker\Semantic\IDuplicateSemantic[] */
    private function getDuplicators()
    {
        $duplicators = [
            $this->getSemanticTitleFactory(),
            $this->getSemanticDescriptionFactory(),
            $this->getSemanticStatusFactory(),
            $this->getSemanticContributorFactory(),
            $this->getSemanticTooltipFactory(),
            new SemanticProgressDuplicator(new SemanticProgressDao()),
            new SemanticDoneDuplicator(
                new SemanticDoneDao(),
                new Tracker_Semantic_StatusDao()
            ),
        ];

        EventManager::instance()->processEvent(
            self::TRACKER_EVENT_GET_SEMANTIC_DUPLICATORS,
            ['duplicators' => &$duplicators]
        );

        return $duplicators;
    }
}
