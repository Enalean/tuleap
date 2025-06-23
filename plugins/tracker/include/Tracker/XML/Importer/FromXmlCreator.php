<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Tracker\XML\Importer;

use Project;
use SimpleXMLElement;
use Tracker_FormElementFactory;
use TrackerFactory;
use TrackerFromXmlException;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Project\XML\Import\ExternalFieldsExtractor;
use Tuleap\Tracker\Creation\TrackerCreationDataChecker;
use Tuleap\Tracker\Creation\TrackerCreationNotificationsSettings;
use Tuleap\Tracker\Creation\TrackerCreationNotificationsSettingsFromXmlBuilder;
use Tuleap\Tracker\Creation\TrackerCreationSettings;
use Tuleap\Tracker\FormElement\Field\XMLCriteriaValueCache;
use Tuleap\Tracker\Tracker;
use Tuleap\Tracker\XML\TrackerXmlImportFeedbackCollector;
use Tuleap\XML\PHPCast;
use Tuleap\XML\SimpleXMLElementBuilder;
use XML_RNGValidator;

final readonly class FromXmlCreator implements CreateFromXml
{
    public function __construct(
        private TrackerFactory $tracker_factory,
        private Tracker_FormElementFactory $formelement_factory,
        private GetInstanceFromXml $get_instance_from_xml,
        private XML_RNGValidator $rng_validator,
        private ExternalFieldsExtractor $external_fields_extractor,
        private TrackerCreationDataChecker $creation_data_checker,
        private TrackerCreationNotificationsSettingsFromXmlBuilder $notifications_settings_from_xml_builder,
        private TrackerXmlImportFeedbackCollector $feedback_collector,
        private \Psr\Log\LoggerInterface $logger,
    ) {
    }

    public function createFromXML(
        SimpleXMLElement $xml_element,
        Project $project,
        string $name,
        string $description,
        string $itemname,
        ?string $color,
        array $created_trackers_mapping,
        array &$xml_fields_mapping,
        array &$reports_xml_mapping,
        array &$renderers_xml_mapping,
    ): Tracker {
        $this->creation_data_checker->checkAtProjectCreation((int) $project->getId(), $name, $itemname);

        $partial_element = SimpleXMLElementBuilder::buildSimpleXMLElementToLoadHugeFiles((string) $xml_element->asXml());
        $this->external_fields_extractor->extractExternalFieldsFromTracker($partial_element);
        $this->rng_validator->validate(
            $partial_element,
            dirname(__DIR__) . '/../../../resources/tracker.rng'
        );

        $tracker = $this->get_instance_from_xml->getInstanceFromXML(
            $xml_element,
            $project,
            $name,
            $description,
            $itemname,
            $color,
            $created_trackers_mapping,
            $xml_fields_mapping,
            $reports_xml_mapping,
            $renderers_xml_mapping,
        );
        //Testing consistency of the imported tracker before updating database
        if ($tracker->testImport()) {
            $attributes                   = $xml_element->attributes();
            $is_displayed_in_new_dropdown = $attributes !== null && isset($attributes['is_displayed_in_new_dropdown']) && $attributes['is_displayed_in_new_dropdown'] !== null
                ? PHPCast::toBoolean($attributes['is_displayed_in_new_dropdown'])
                : false;
            $is_private_comment_used      = $attributes !== null && isset($attributes['use_private_comments']) && $attributes['use_private_comments'] !== null
                ? PHPCast::toBoolean($attributes['use_private_comments'])
                : true;
            $is_move_artifacts_enabled    = $attributes !== null && isset($attributes['enable_move_artifacts']) && $attributes['enable_move_artifacts'] !== null
                ? PHPCast::toBoolean($attributes['enable_move_artifacts'])
                : true;

            $settings = new TrackerCreationSettings(
                $is_displayed_in_new_dropdown,
                $is_private_comment_used,
                $is_move_artifacts_enabled,
            );

            $this->notifications_settings_from_xml_builder
                ->getCreationNotificationsSettings($attributes, $tracker)
                ->andThen(fn (TrackerCreationNotificationsSettings $notifications_settings) => $this->saveTracker($tracker, $settings, $notifications_settings))
                ->match(
                    static fn (int $tracker_id)  => $tracker->setId($tracker_id),
                    function (string $error): void {
                        throw new TrackerFromXmlException($error);
                    }
                );
        } else {
            throw new TrackerFromXmlException('XML file cannot be imported');
        }

        $this->displayWarnings();

        XMLCriteriaValueCache::clearInstances();
        $this->formelement_factory->clearCaches();
        $this->tracker_factory->clearCaches();

        return $tracker;
    }

    /**
     * @return Ok<int>|Err<string>
     */
    private function saveTracker(
        Tracker $tracker,
        TrackerCreationSettings $settings,
        TrackerCreationNotificationsSettings $notifications_settings,
    ): Ok|Err {
        $tracker_id = $this->tracker_factory->saveObject($tracker, $settings, $notifications_settings);
        if (! $tracker_id) {
            return Result::err(
                dgettext(
                    'tuleap-tracker',
                    'Oops. Something weird occured. Unable to create the tracker. Please try again.'
                )
            );
        }

        return Result::ok($tracker_id);
    }

    private function displayWarnings(): void
    {
        if (empty($this->feedback_collector->getWarnings())) {
            return;
        }

        $this->feedback_collector->displayWarnings($this->logger);
    }
}
