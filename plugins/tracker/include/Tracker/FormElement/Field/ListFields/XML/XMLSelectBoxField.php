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

namespace Tuleap\Tracker\FormElement\Field\ListFields\XML;

use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStatic\XML\XMLBindStaticValue;
use Tuleap\Tracker\FormElement\Field\XML\XMLField;
use Tuleap\Tracker\XML\IDGenerator;

final class XMLSelectBoxField extends XMLField
{
    /**
     * @var \Tracker_FormElement_Field_List_Bind_Static::TYPE | \Tracker_FormElement_Field_List_Bind_Users::TYPE | \Tracker_FormElement_Field_List_Bind_Ugroups::TYPE
     * @readonly
     */
    private $bind_type;
    /**
     * @var XMLBindStaticValue[]
     * @readonly
     */
    public $bind_values = [];

    /**
     * @var bool
     * @readonly
     */
    private $is_rank_alphanumeric = false;

    /**
     * @param string|IDGenerator $id
     */
    public function __construct($id, string $name)
    {
        parent::__construct($id, \Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE, $name);
    }

    /**
     * @psalm-mutation-free
     */
    public function withAlphanumericRank(): self
    {
        $new                       = clone $this;
        $new->is_rank_alphanumeric = true;
        return $new;
    }

    /**
     * @psalm-mutation-free
     */
    public function withBindStatic(): self
    {
        $new            = clone $this;
        $new->bind_type = \Tracker_FormElement_Field_List_Bind_Static::TYPE;
        return $new;
    }

    /**
     * @psalm-mutation-free
     */
    public function withStaticValues(XMLBindStaticValue ...$bind_value): self
    {
        $new              = clone $this;
        $new->bind_type   = \Tracker_FormElement_Field_List_Bind_Static::TYPE;
        $new->bind_values = array_merge($new->bind_values, $bind_value);
        return $new;
    }

    public function export(\SimpleXMLElement $form_elements): \SimpleXMLElement
    {
        if (! $this->bind_type) {
            throw new \LogicException(self::class . ' must have a bind type');
        }
        $form_element = parent::export($form_elements);

        $bind = $form_element->addChild('bind');
        $bind->addAttribute('type', $this->bind_type);
        $bind->addAttribute('is_rank_alpha', $this->is_rank_alphanumeric ? '1' : '0');
        if (count($this->bind_values) > 0) {
            $items = $bind->addChild('items');
            foreach ($this->bind_values as $bind_value) {
                $bind_value->export($items);
            }
        }

        return $form_element;
    }
}
