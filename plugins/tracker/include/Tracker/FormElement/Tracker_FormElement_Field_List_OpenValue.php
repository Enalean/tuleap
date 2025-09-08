<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015-present. All Rights Reserved.
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

use Tuleap\Tracker\FormElement\Field\List\OpenListField;
use Tuleap\Tracker\FormElement\Field\TrackerField;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_FormElement_Field_List_OpenValue extends Tracker_FormElement_Field_List_Value
{
    public const OPEN_PREFIX = 'o';

    protected $label;

    public function __construct(\Tuleap\DB\UUID $uuid, $id, $label, bool $is_hidden)
    {
        parent::__construct($uuid, $id, $is_hidden);
        $this->label = $label;
    }

    public function __toString(): string
    {
        return $this->getLabel();
    }

    #[\Override]
    public function getLabel(): string
    {
        return $this->label;
    }

    #[\Override]
    public function getJsonId()
    {
        return self::OPEN_PREFIX . $this->getId();
    }

    public function getUsername()
    {
        return $this->getLabel();
    }

    #[\Override]
    public function getFullRESTValue(TrackerField $field)
    {
        if (! $field instanceof OpenListField) {
            throw new InvalidArgumentException('Expected ' . OpenListField::class . ', got ' . $field::class);
        }
        return [
            'id'    => $this->getJsonId(),
            'label' => $this->getLabel(),
        ];
    }
}
