<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\XML;

use SimpleXMLElement;
use Tracker;
use Tuleap\Tracker\Artifact\XML\XMLArtifact;
use Tuleap\Tracker\Creation\TrackerCreationDataChecker;
use Tuleap\Tracker\FormElement\Container\XML\XMLContainer;
use Tuleap\Tracker\FormElement\XML\XMLFormElement;
use Tuleap\Tracker\FormElement\XML\XMLFormElementFlattenedCollection;
use Tuleap\Tracker\Report\XML\XMLReport;
use Tuleap\Tracker\Semantic\XML\XMLSemantic;
use Tuleap\Tracker\TrackerColor;
use Tuleap\Tracker\TrackerIsInvalidException;
use Tuleap\Tracker\Workflow\XML\XMLWorkflow;

final class XMLTracker
{
    /**
     * @var string
     * @readonly
     */
    private $id;
    /**
     * @var string
     * @readonly
     */
    private $name = '';
    /**
     * @readonly
     */
    public string $item_name;
    /**
     * @var string
     * @readonly
     */
    private $description = '';
    /**
     * @var TrackerColor
     * @readonly
     */
    private $color;
    /**
     * @var string
     * @readonly
     */
    private $parent_id = '0';
    /**
     * @var string
     */
    private $submit_instructions = '';
    /**
     * @var string
     */
    private $browse_instructions = '';
    /**
     * @var XMLFormElement[]
     * @readonly
     */
    private $form_elements = [];
    /**
     * @var XMLReport[]
     * @readonly
     */
    private $reports = [];
    /**
     * @var XMLSemantic[]
     * @readonly
     */
    private $semantics = [];
    /**
     * @var XMLArtifact[]
     * @readonly
     */
    private $artifacts = [];
    /**
     * @readonly
     */
    private ?XMLWorkflow $workflow = null;
    /**
     * @readonly
     */
    private bool $is_promoted = false;

    /**
     * @param string|IDGenerator $id
     * @throws TrackerIsInvalidException
     */
    public function __construct($id, string $item_name)
    {
        if ($id instanceof IDGenerator) {
            $this->id = sprintf('%s%d', Tracker::XML_ID_PREFIX, $id->getNextId());
        } else {
            $this->id = $id;
        }
        if (! TrackerCreationDataChecker::isShortNameValid($item_name)) {
            throw TrackerIsInvalidException::shortnameIsInvalid($item_name);
        }
        $this->item_name = $item_name;
        $this->color     = TrackerColor::default();
    }

    /**
     * @psalm-mutation-free
     */
    public function withName(string $name): self
    {
        $new       = clone $this;
        $new->name = $name;
        return $new;
    }

    /**
     * @psalm-mutation-free
     */
    public function withPromoted(): self
    {
        $new              = clone $this;
        $new->is_promoted = true;

        return $new;
    }

    /**
     * @psalm-mutation-free
     */
    public function withDescription(string $description): self
    {
        $new              = clone $this;
        $new->description = $description;
        return $new;
    }

    /**
     * @psalm-mutation-free
     */
    public function withColor(TrackerColor $color): self
    {
        $new        = clone $this;
        $new->color = $color;
        return $new;
    }

    /**
     * @psalm-mutation-free
     */
    public function withParentId(string $parent_id): self
    {
        $new            = clone $this;
        $new->parent_id = $parent_id;
        return $new;
    }

    /**
     * @psalm-mutation-free
     */
    public function withSubmitInstructions(string $submit_instructions): self
    {
        $new                      = clone $this;
        $new->submit_instructions = $submit_instructions;
        return $new;
    }

    /**
     * @psalm-mutation-free
     */
    public function withBrowseInstructions(string $browse_instructions): self
    {
        $new                      = clone $this;
        $new->browse_instructions = $browse_instructions;
        return $new;
    }

    /**
     * @psalm-mutation-free
     */
    public function withWorkflow(XMLWorkflow $workflow): self
    {
        $new           = clone $this;
        $new->workflow = $workflow;
        return $new;
    }

    /**
     * @psalm-mutation-free
     */
    public function withFormElement(XMLFormElement ...$form_elements): self
    {
        $new                = clone $this;
        $new->form_elements = array_merge($this->form_elements, $form_elements);
        return $new;
    }

    /**
     * @psalm-mutation-free
     */
    public function appendFormElement(string $name, XMLFormElement $form_element): self
    {
        $new                = clone $this;
        $new->form_elements = [];
        foreach ($this->form_elements as $element) {
            if ($element instanceof XMLContainer) {
                $new->form_elements[] = $element->appendFormElements($name, $form_element);
            } else {
                $new->form_elements[] = $element;
            }
        }
        return $new;
    }

    /**
     * @psalm-mutation-free
     */
    public function withReports(XMLReport ...$reports): self
    {
        $new          = clone $this;
        $new->reports = array_merge($new->reports, $reports);
        return $new;
    }

    /**
     * @psalm-mutation-free
     */
    public function withSemantics(XMLSemantic ...$semantic): self
    {
        $new            = clone $this;
        $new->semantics = array_merge($new->semantics, $semantic);
        return $new;
    }

    /**
     * @psalm-mutation-free
     */
    public function withArtifact(XMLArtifact $artifact): self
    {
        $new              = clone $this;
        $new->artifacts[] = $artifact;
        return $new;
    }

    public static function fromTracker(Tracker $tracker): self
    {
        return (new self($tracker->getXMLId(), $tracker->getItemName()))
            ->withName($tracker->getName())
            ->withDescription($tracker->getDescription())
            ->withColor($tracker->getColor())
            ->withSubmitInstructions($tracker->submit_instructions ?? '')
            ->withBrowseInstructions($tracker->browse_instructions ?? '');
    }

    public static function fromTrackerInProjectContext(Tracker $tracker): self
    {
        $parent = $tracker->getParent();
        return self::fromTracker($tracker)
            ->withParentId($parent !== null ? $parent->getXMLId() : "0");
    }

    public function export(SimpleXMLElement $trackers_xml): SimpleXMLElement
    {
        $tracker_xml = $trackers_xml->addChild('tracker');
        return $this->exportTracker($tracker_xml);
    }

    public function exportTracker(SimpleXMLElement $tracker_xml): SimpleXMLElement
    {
        $form_elements_flattened_collection = XMLFormElementFlattenedCollection::buildFromFormElements(...$this->form_elements);

        $tracker_xml->addAttribute('id', $this->id);
        $tracker_xml->addAttribute('parent_id', $this->parent_id);
        if ($this->is_promoted) {
            $tracker_xml->addAttribute('is_displayed_in_new_dropdown', '1');
        }

        $cdata_section_factory = new \XML_SimpleXMLCDATAFactory();
        $cdata_section_factory->insert($tracker_xml, 'name', $this->name);
        $cdata_section_factory->insert($tracker_xml, 'item_name', $this->item_name);
        $cdata_section_factory->insert($tracker_xml, 'description', $this->description);
        $cdata_section_factory->insert($tracker_xml, 'color', $this->color->getName());

        if ($this->submit_instructions !== '') {
            $cdata_section_factory->insert($tracker_xml, 'submit_instructions', $this->submit_instructions);
        }
        if ($this->browse_instructions !== '') {
            $cdata_section_factory->insert($tracker_xml, 'browse_instructions', $this->browse_instructions);
        }

        $tracker_xml->addChild('cannedResponses');

        $tracker_xml->addChild('formElements');
        if (count($this->form_elements) > 0) {
            foreach ($this->form_elements as $form_element) {
                $form_element->export($tracker_xml->formElements);
            }
        }

        if (count($this->semantics) > 0) {
            $tracker_xml->addChild('semantics');
            foreach ($this->semantics as $semantic) {
                $semantic->export($tracker_xml->semantics, $form_elements_flattened_collection);
            }
        }

        if (count($this->reports) > 0) {
            $tracker_xml->addChild('reports');
            foreach ($this->reports as $report) {
                $report->export($tracker_xml->reports, $form_elements_flattened_collection);
            }
        }

        if ($this->workflow) {
            $this->workflow->export($tracker_xml, $form_elements_flattened_collection);
        }

        if (count($form_elements_flattened_collection) > 0) {
            $permissions_xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><permissions />');
            foreach ($form_elements_flattened_collection as $form_element) {
                $form_element->exportPermissions($permissions_xml);
            }

            if (isset($permissions_xml->permission)) {
                $dom_tracker     = dom_import_simplexml($tracker_xml);
                $dom_permissions = dom_import_simplexml($permissions_xml);
                if (! $dom_tracker->ownerDocument) {
                    throw new \LogicException('tracker node must have a ownerDocument property');
                }
                $dom_permissions = $dom_tracker->ownerDocument->importNode($dom_permissions, true);
                $dom_tracker->appendChild($dom_permissions);
            }
        }

        if (count($this->artifacts) > 0) {
            $tracker_xml->addChild('artifacts');
            foreach ($this->artifacts as $artifact) {
                $artifact->export($tracker_xml->artifacts, $form_elements_flattened_collection);
            }
        }

        return $tracker_xml;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
