<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Project\Service;

final class ServiceJSONPresenter
{
    /** @var int */
    public $id;
    /** @var string */
    public $short_name;
    /** @var string */
    public $label;
    /** @var string */
    public $icon_name;
    /** @var string */
    public $link;
    /** @var string */
    public $description;
    /** @var bool */
    public $is_active;
    /** @var bool */
    public $is_used;
    /** @var bool */
    public $is_in_iframe;
    /** @var bool */
    public $is_in_new_tab;
    /** @var int */
    public $rank;
    /** @var bool */
    public $is_project_scope;
    /** @var bool */
    public $is_link_customizable;

    public function __construct(
        int $id,
        string $short_name,
        string $label,
        string $icon_name,
        string $link,
        string $description,
        bool $is_active,
        bool $is_used,
        bool $is_in_iframe,
        bool $is_in_new_tab,
        int $rank,
        bool $is_project_scope,
        bool $is_link_customizable
    ) {
        $this->id                   = $id;
        $this->short_name           = $short_name;
        $this->label                = $label;
        $this->icon_name            = $icon_name;
        $this->link                 = $link;
        $this->description          = $description;
        $this->is_active            = $is_active;
        $this->is_used              = $is_used;
        $this->is_in_new_tab        = $is_in_new_tab;
        $this->is_in_iframe         = $is_in_iframe;
        $this->rank                 = $rank;
        $this->is_project_scope     = $is_project_scope;
        $this->is_link_customizable = $is_link_customizable;
    }
}
