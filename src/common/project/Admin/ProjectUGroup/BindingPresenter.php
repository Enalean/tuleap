<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Project\Admin\ProjectUGroup;

class BindingPresenter
{
    public $add_binding;
    public $has_clones;
    public $clones;
    public $current_binding;
    public $modals;

    public function __construct(array $add_binding, array $current_binding, array $modals, array $clones)
    {
        $this->add_binding     = $add_binding;
        $this->has_clones      = count($clones) > 0;
        $this->clones          = $clones;
        $this->current_binding = $current_binding;
        $this->modals          = $modals;
    }
}
