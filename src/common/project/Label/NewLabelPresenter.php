<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\Project\Label;

use Tuleap\Color\AllowedColorsCollection;
use Tuleap\Color\ColorPresenterFactory;

class NewLabelPresenter extends LabelPresenter
{
    public function __construct(ColorPresenterFactory $color_factory)
    {
        $id                = 0;
        $name              = '';
        $is_outline        = true;
        $is_used           = false;
        $color             = AllowedColorsCollection::DEFAULT_COLOR;
        $colors_presenters = $color_factory->getColorsPresenters(AllowedColorsCollection::DEFAULT_COLOR);

        parent::__construct($id, $name, $is_outline, $color, $is_used, $colors_presenters);

        $this->save            = _('Create label');
        $this->warning_message = _('"%s" label already exists. Please choose another one.');

        $this->is_save_allowed_on_duplicate = false;
    }
}
