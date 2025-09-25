<?php
/*
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

namespace Tuleap\Tracker\FormElement\XML;

use Tuleap\Tracker\XML\IDGenerator;

abstract class XMLFormElement
{
    /**
     * @var string
     * @readonly
     */
    public $id;
    /**
     * @var string
     * @readonly
     */
    public $name;
    /**
     * @var int
     * @readonly
     */
    private $rank = 1;
    /**
     * @var bool
     * @readonly
     */
    private $use_it = true;
    /**
     * @var string
     * @readonly
     */
    public $label = '';
    /**
     * @var string
     * @readonly
     */
    public $type;
    /**
     * @var string
     * @readonly
     */
    private $description = '';
    /**
     * @var bool
     * @readonly
     */
    private $required = false;
    /**
     * @var bool
     * @readonly
     */
    private $notifications = false;
    /**
     * @var ?int
     * @readonly
     */
    private $original_field_id;
    /**
     * @var ?int
     * @readonly
     */
    private $tracker_id;
    /**
     * @var ?int
     * @readonly
     */
    private $parent_id;
    /**
     * @var 'P'|'S'
     * @readonly
     */
    private $scope = 'P';

    public function __construct(string|IDGenerator $id, string $type, string $name)
    {
        if ($id instanceof IDGenerator) {
            $this->id = sprintf('%s%d', \Tuleap\Tracker\FormElement\TrackerFormElement::XML_ID_PREFIX, $id->getNextId());
        } else {
            $this->id = $id;
        }
        $this->type = $type;
        $this->name = $name;
    }

    /**
     * @return static
     * @psalm-mutation-free
     */
    public function fromFormElement(\Tuleap\Tracker\FormElement\TrackerFormElement $form_element): self
    {
        $new = clone $this;
        if ($form_element->rank !== null) {
            $new->rank = (int) $form_element->rank;
        }
        if ($form_element->label) {
            $new->label = $form_element->label;
        }
        if ($form_element->use_it === false) {
            $new->use_it = false;
        }
        if ($form_element->description) {
            $new->description = $form_element->getDescription();
        }
        if ($form_element->notifications) {
            $new->notifications = true;
        }
        if ($form_element->required) {
            $new->required = true;
        }
        if ($form_element->parent_id !== null) {
            $new->parent_id = $form_element->parent_id;
        }
        if ($form_element->id) {
            $new->original_field_id = $form_element->id;
        }
        if ($form_element->tracker_id) {
            $new->tracker_id = $form_element->tracker_id;
        }
        return $new;
    }

    /**
     * @return static
     * @psalm-mutation-free
     */
    public function withRank(int $rank): self
    {
        $new       = clone $this;
        $new->rank = $rank;
        return $new;
    }

    /**
     * @return static
     * @psalm-mutation-free
     */
    public function withUseIt(bool $use_it): self
    {
        $new         = clone $this;
        $new->use_it = $use_it;
        return $new;
    }

    /**
     * @return static
     * @psalm-mutation-free
     */
    public function withLabel(string $label): self
    {
        $new        = clone $this;
        $new->label = $label;
        return $new;
    }

    /**
     * @return static
     * @psalm-mutation-free
     */
    public function withDescription(string $description): self
    {
        $new              = clone $this;
        $new->description = $description;
        return $new;
    }

    /**
     * @return static
     * @psalm-mutation-free
     */
    public function withNotifications(bool $notifications): self
    {
        $new                = clone $this;
        $new->notifications = $notifications;
        return $new;
    }

    /**
     * @return static
     * @psalm-mutation-free
     */
    public function withRequired(bool $required): self
    {
        $new           = clone $this;
        $new->required = $required;
        return $new;
    }

    public function export(\SimpleXMLElement $form_elements): \SimpleXMLElement
    {
        $formelement_node = $form_elements->addChild($this->getXMLTagName());
        $formelement_node->addAttribute('type', $this->type);

        $formelement_node->addAttribute('ID', $this->id);
        $formelement_node->addAttribute('rank', (string) $this->rank);
        if ($this->original_field_id !== null) {
            $formelement_node->addAttribute('id', (string) $this->original_field_id);
        }
        if ($this->tracker_id !== null) {
            $formelement_node->addAttribute('tracker_id', (string) $this->tracker_id);
        }
        if ($this->parent_id !== null) {
            $formelement_node->addAttribute('parent_id', (string) $this->parent_id);
        }
        if (! $this->use_it) {
            $formelement_node->addAttribute('use_it', '0');
        }
        if ($this->scope !== 'P') {
            $formelement_node->addAttribute('scope', 'S');
        }
        if ($this->required) {
            $formelement_node->addAttribute('required', '1');
        }
        if ($this->notifications) {
            $formelement_node->addAttribute('notifications', '1');
        }

        $simplexml_cdata_factory = new \XML_SimpleXMLCDATAFactory();
        $simplexml_cdata_factory->insert($formelement_node, 'name', $this->name);
        $label = $this->label;
        if ($label === '') {
            $label = $this->name;
        }
        $simplexml_cdata_factory->insert($formelement_node, 'label', $label);
        if ($this->description !== '') {
            $simplexml_cdata_factory->insert($formelement_node, 'description', $this->description);
        }

        return $formelement_node;
    }

    protected function getXMLTagName(): string
    {
        return \Tuleap\Tracker\FormElement\TrackerFormElement::XML_TAG;
    }

    abstract public function exportPermissions(\SimpleXMLElement $form_elements): void;
}
