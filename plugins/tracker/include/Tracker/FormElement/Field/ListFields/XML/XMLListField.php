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
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindUsers\XML\XMLBindUsersValue;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\XML\XMLBindValue;
use Tuleap\Tracker\FormElement\Field\XML\XMLField;

abstract class XMLListField extends XMLField
{
    /**
     * @var null | \Tracker_FormElement_Field_List_Bind_Static::TYPE | \Tracker_FormElement_Field_List_Bind_Users::TYPE | \Tracker_FormElement_Field_List_Bind_Ugroups::TYPE
     * @readonly
     */
    public ?string $bind_type = null;
    /**
     * @var XMLBindValue[]
     * @readonly
     */
    public array $bind_values = [];

    /**
     * @readonly
     */
    private bool $is_rank_alphanumeric = false;

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
        if ($this->bind_type !== null && $this->bind_type !== \Tracker_FormElement_Field_List_Bind_Static::TYPE) {
            throw new \LogicException(sprintf('Cannot mix bind type, %s already set', $this->bind_type));
        }
        $new              = clone $this;
        $new->bind_type   = \Tracker_FormElement_Field_List_Bind_Static::TYPE;
        $new->bind_values = array_merge($new->bind_values, $bind_value);
        return $new;
    }

    /**
     * @psalm-mutation-free
     */
    public function withUsersValues(XMLBindUsersValue ...$bind_value): self
    {
        if ($this->bind_type !== null && $this->bind_type !== \Tracker_FormElement_Field_List_Bind_Users::TYPE) {
            throw new \LogicException(sprintf('Cannot mix bind type, %s already set', $this->bind_type));
        }
        $new              = clone $this;
        $new->bind_type   = \Tracker_FormElement_Field_List_Bind_Users::TYPE;
        $new->bind_values = array_merge($new->bind_values, $bind_value);
        return $new;
    }

    #[\Override]
    public function export(\SimpleXMLElement $form_elements): \SimpleXMLElement
    {
        if (! isset($this->bind_type)) {
            throw new \LogicException(self::class . ' must have a bind type');
        }
        $form_element = parent::export($form_elements);

        $bind = $form_element->addChild('bind');
        $bind->addAttribute('type', $this->bind_type);
        if ($this->bind_type === \Tracker_FormElement_Field_List_Bind_Static::TYPE) {
            $bind->addAttribute('is_rank_alpha', $this->is_rank_alphanumeric ? '1' : '0');
        }
        if (count($this->bind_values) > 0) {
            $items = $bind->addChild('items');
            foreach ($this->bind_values as $bind_value) {
                $bind_value->export($bind, $items);
            }
        }

        return $form_element;
    }
}
