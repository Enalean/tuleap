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
use Tracker_FormElement_Field_List_BindDecoratorDao;

class BoundDecoratorEditor
{
    /**
     * @var Tracker_FormElement_Field_List_BindDecoratorDao
     */
    private $decorator_dao;

    public function __construct(Tracker_FormElement_Field_List_BindDecoratorDao $decorator_dao)
    {
        $this->decorator_dao = $decorator_dao;
    }
    public function update(\Tracker_FormElement_Field $field, int $value_id, string $color, bool $will_be_required): void
    {
        if ($value_id === \Tracker_FormElement_Field_List::NONE_VALUE) {
            $this->updateNone($field, $value_id, $color, $will_be_required);
            return;
        }


        $this->updateColor($color, $value_id);
    }

    private function updateColor(string $color, int $value_id): void
    {
        if (! Tracker_FormElement_Field_List_BindDecorator::isHexaColor($color)) {
            $this->decorator_dao->updateTlpColor($value_id, $color);
            return;
        }

        [$r, $g, $b] = ColorHelper::HexaToRGB($color);
        $this->decorator_dao->updateColor($value_id, $r, $g, $b);
    }

    private function updateNone(\Tracker_FormElement_Field $field, int $value_id, string $color, bool $will_be_required): void
    {
        if ($will_be_required === true) {
            $this->decorator_dao->delete((int) $field->getId(), $value_id);
            return;
        }

        if (! Tracker_FormElement_Field_List_BindDecorator::isHexaColor($color)) {
            $this->decorator_dao->updateNoneTlpColor((int) $field->getId(), $color);
            return;
        }

        [$r, $g, $b] = ColorHelper::HexaToRGB($color);
        $this->decorator_dao->updateNoneLegacyColor((int) $field->getId(), $r, $g, $b);
    }
}
