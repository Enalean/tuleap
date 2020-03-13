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

namespace Tuleap\Project\XML\Import;

use EventManager;
use SimpleXMLElement;
use Tuleap\Event\Events\ImportValidateChangesetExternalField;
use Tuleap\Event\Events\ImportValidateExternalFields;

class ExternalFieldsExtractor
{
    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(EventManager $event_manager)
    {
        $this->event_manager = $event_manager;
    }

    private function extractExternalFieldsFromFormElements(SimpleXMLElement $xml_form_element): void
    {
        foreach ($xml_form_element as $index => $form_element) {
            if ($form_element->externalField) {
                $external_field_id        = (string) $form_element->externalField['ID'];
                $validate_external_fields = new ImportValidateExternalFields($form_element->externalField);
                $this->event_manager->processEvent($validate_external_fields);
                $this->removeReferencesToExternalField($xml_form_element, $external_field_id);
                unset($form_element->externalField);
            }
            $this->extractExternalFieldsFromFormElements($form_element);
        }
    }

    public function extractExternalFieldFromProjectElement(SimpleXMLElement $xml_element): void
    {
        if ($xml_element->trackers->tracker) {
            foreach ($xml_element->trackers->tracker as $xml_tracker) {
                $this->extractExternalFieldsFromTracker($xml_tracker);
            }
        }
    }

    private function removeReferencesToExternalField(SimpleXMLElement $xml_element, string $external_field_id): void
    {
        foreach ($xml_element->xpath("//*[@REF='$external_field_id']") as $unused) {
            unset($xml_element->xpath("//*[@REF='$external_field_id']")[0][0]);
        }
    }

    public function extractExternalFieldsFromArtifact(SimpleXMLElement $xml_artifact): void
    {
        foreach ($xml_artifact->changeset as $field_change) {
            if ($field_change->external_field_change) {
                $validate_external_field_changeset = new ImportValidateChangesetExternalField($field_change->external_field_change);
                $this->event_manager->processEvent($validate_external_field_changeset);
                unset($field_change->external_field_change);
            }
        }
    }

    public function extractExternalFieldsFromTracker(SimpleXMLElement $xml_tracker): void
    {
        $this->extractExternalFieldsFromFormElements($xml_tracker->formElements);

        if ($xml_tracker->artifacts->artifact) {
            foreach ($xml_tracker->artifacts->artifact as $index => $field_change) {
                $this->extractExternalFieldsFromArtifact($field_change);
            }
        }
    }
}
