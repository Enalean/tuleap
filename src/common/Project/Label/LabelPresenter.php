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
use Tuleap\Color\ColorPresenter;

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

    /** @var string */
    public $save;

    /** @var bool */
    public $is_save_allowed_on_duplicate;

    public function __construct($id, $name, $is_outline, $color, $is_used, array $colors_presenters)
    {
        $this->id                = $id;
        $this->name              = $name;
        $this->is_outline        = $is_outline;
        $this->color             = $color;
        $this->is_used           = $is_used;
        $this->colors_presenters = $colors_presenters;

        $this->save         = _('Update label');
        $this->delete_title = sprintf(
            _('Delete %s'),
            $this->name
        );
        $this->edit_title = sprintf(
            _('Edit %s'),
            $this->name
        );

        $purifier = Codendi_HTMLPurifier::instance();
        $this->purified_delete_message = $purifier->purify(
            sprintf(
                _("Wow, wait a minute. You're about to delete the label <b>%s</b>. Please confirm your action."),
                $purifier->purify($this->name)
            ),
            CODENDI_PURIFIER_FULL
        );

        $this->warning_message = _('"%s" label already exists. If you save your modifications, both labels will be merged.');

        $this->is_save_allowed_on_duplicate = true;
    }

    public function switchToUsed()
    {
        $this->is_used = true;
    }
}
