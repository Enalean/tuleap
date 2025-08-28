<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ListFields\Bind;

use ColorHelper;
use Tracker_FormElement_Field_List_BindDecorator;

class BoundDecoratorSaver
{
    /**
     * @var BindDecoratorDao
     */
    private $bind_decorator_dao;

    public function __construct(BindDecoratorDao $bind_decorator_dao)
    {
        $this->bind_decorator_dao = $bind_decorator_dao;
    }

    public function save(\Tuleap\Tracker\FormElement\Field\TrackerField $field, int $value_id, string $color): void
    {
        if ($value_id === \Tuleap\Tracker\FormElement\Field\ListField::NONE_VALUE) {
            $this->saveNone($field, $value_id, $color);
            return;
        }

        $this->saveColor($color, $value_id);
    }

    private function saveNone(\Tuleap\Tracker\FormElement\Field\TrackerField $field, int $value_id, string $color): void
    {
        if (! Tracker_FormElement_Field_List_BindDecorator::isHexaColor($color)) {
            $this->bind_decorator_dao->saveNoneTlpColor($field->getId(), $color);
            return;
        }

        [$r, $g, $b] = ColorHelper::HexaToRGB($color);
        $this->bind_decorator_dao->saveNoneLegacyColor($field->getId(), $r, $g, $b);
    }

    private function saveColor(string $color, int $value_id): void
    {
        if (! Tracker_FormElement_Field_List_BindDecorator::isHexaColor($color)) {
            $this->bind_decorator_dao->saveTlpColor($value_id, $color);
            return;
        }

        [$r, $g, $b] = ColorHelper::HexaToRGB($color);
        $this->bind_decorator_dao->save($value_id, $r, $g, $b);
    }
}
