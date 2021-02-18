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

namespace Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStatic\XML;

use Tuleap\Tracker\XML\IDGenerator;

class XMLBindStaticValue
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
    public $id_for_field_change;
    /**
     * @var string
     * @readonly
     */
    public $label;

    /**
     * @param string|IDGenerator $id
     */
    public function __construct($id, string $label)
    {
        if ($id instanceof IDGenerator) {
            $next_id                   = $id->getNextId();
            $this->id_for_field_change = (string) $next_id;
            $this->id                  = sprintf('V%d', $next_id);
        } else {
            $this->id_for_field_change = substr($id, 1);
            $this->id                  = $id;
        }
        $this->label = $label;
    }

    public function export(\SimpleXMLElement $bind): void
    {
        $item = $bind->addChild('item');
        $item->addAttribute('ID', $this->id);
        $item->addAttribute('label', $this->label);
    }
}
