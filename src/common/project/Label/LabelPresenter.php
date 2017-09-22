<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

use Codendi_HTMLPurifier;
use Tuleap\Label\ColorPresenter;

class LabelPresenter
{
    /** @var int */
    public $id;

    /** @var string */
    public $name;

    /** @var bool */
    public $is_used;

    /** @var string */
    public $delete_title;

    /** @var string */
    public $edit_title;

    /** @var string */
    public $warning_message;

    /** @var string */
    public $purified_delete_message;

    /** @var bool */
    public $is_outline;

    /** @var string */
    public $color;

    /** @var ColorPresenter[] */
    public $colors_presenters;

    public function __construct($id, $name, $is_outline, $color, $is_used, array $colors_presenters)
    {
        $this->id                = $id;
        $this->name              = $name;
        $this->is_outline        = $is_outline;
        $this->color             = $color;
        $this->is_used           = $is_used;
        $this->colors_presenters = $colors_presenters;

        $this->delete_title = sprintf(
            _('Delete %s'),
            $this->name
        );
        $this->edit_title = sprintf(
            _('Edit %s'),
            $this->name
        );

        $this->purified_delete_message = Codendi_HTMLPurifier::instance()->purify(
            sprintf(
                _("Wow, wait a minute. You're about to delete the label <b>%s</b>. Please confirm your action."),
                $this->name
            ),
            CODENDI_PURIFIER_LIGHT
        );

        $this->warning_message = _('"%s" label already exists. If you save your modifications, both labels will be merged.');
    }

    public function switchToUsed()
    {
        $this->is_used = true;
    }
}
