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

namespace Tuleap\Tracker\FormElement\Field\ListFields\Bind\XML;

use Tuleap\Tracker\FormElement\XML\XMLFormElementFlattenedCollection;

final class XMLBindValueReferenceByLabel implements XMLBindValueReference
{
    /**
     * @var string
     */
    private $label;
    /**
     * @var string
     */
    private $field_name;

    public function __construct(string $field_name, string $label)
    {
        $this->label      = $label;
        $this->field_name = $field_name;
    }

    #[\Override]
    public function getId(XMLFormElementFlattenedCollection $form_elements): string
    {
        return $form_elements->getBindValueByLabel($this->field_name, $this->label)->id;
    }

    #[\Override]
    public function getIdForFieldChange(XMLFormElementFlattenedCollection $form_elements): string
    {
        return $form_elements->getBindValueByLabel($this->field_name, $this->label)->id_for_field_change;
    }
}
