<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow\PostAction\FrozenFields;

use SimpleXMLElement;
use Tracker_FormElement_Field;
use Transition_PostAction;
use Tuleap\Tracker\Workflow\PostAction\Visitor;

class FrozenFields extends Transition_PostAction
{
    public const SHORT_NAME   = 'frozen_fields';
    public const XML_TAG_NAME = 'postaction_frozen_fields';

    /** @var Tracker_FormElement_Field[] */
    private $fields = [];

    public function __construct(\Transition $transition, int $id, array $fields)
    {
        parent::__construct($transition, $id);

        $this->fields = $fields;
    }

    /** @return string */
    public function getShortName()
    {
        return self::SHORT_NAME;
    }

    /** @return int[] */
    public function getFieldIds(): array
    {
        $ids = [];
        foreach ($this->fields as $field) {
            $ids[] = (int) $field->getId();
        }

        return $ids;
    }

    /** @return string */
    public static function getLabel()
    {
        // Not implemented. We do not support the legacy UI for this new post action
        return '';
    }

    /** @return bool */
    public function isDefined()
    {
        // Since we do not support the legacy UI, it is always well defined
        return true;
    }

    /**
     * Export postactions to XML
     *
     * @param SimpleXMLElement &$root       the node to which the postaction is attached (passed by reference)
     * @param array             $xmlMapping correspondance between real ids and xml IDs
     *
     * @return void
     */
    public function exportToXml(SimpleXMLElement $root, $xmlMapping)
    {
        if (count($this->getFieldIds()) > 0) {
            $child = $root->addChild(self::XML_TAG_NAME);
            foreach ($this->getFieldIds() as $field_id) {
                $field_id = array_search($field_id, $xmlMapping);
                if ($field_id !== false) {
                    $child->addChild('field_id')->addAttribute('REF', $field_id);
                }
            }
        }
    }

    /**
     * Get the value of bypass_permissions
     *
     *
     * @return bool
     */
    public function bypassPermissions(Tracker_FormElement_Field $field)
    {
        return false;
    }

    public function accept(Visitor $visitor)
    {
        $visitor->visitFrozenFields($this);
    }
}
