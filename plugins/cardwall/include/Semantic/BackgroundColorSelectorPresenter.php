<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Cardwall\Semantic;

use Tuleap\Tracker\FormElement\TrackerFormElement;

/**
 * @psalm-immutable
 */
final class BackgroundColorSelectorPresenter
{
    /**
     * @var TrackerFormElement[]
     */
    public array $form_elements;
    public bool $has_background_field_defined;
    public bool $has_at_least_one_field_selectable_for_color;
    public string $field_admin_link;

    public function __construct(
        array $form_elements,
        bool $has_background_field_defined,
        string $field_admin_link,
    ) {
        $this->has_at_least_one_field_selectable_for_color = count($form_elements) > 0;
        $this->form_elements                               = $form_elements;
        $this->has_background_field_defined                = $has_background_field_defined;
        $this->field_admin_link                            = $field_admin_link;
    }
}
