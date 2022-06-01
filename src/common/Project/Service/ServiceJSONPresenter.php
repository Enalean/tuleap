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
    public int $id;
    public string $short_name;
    public string $label;
    public string $icon_name;
    public string $link;
    public string $description;
    public bool $is_active;
    public bool $is_used;
    public bool $is_in_iframe;
    public bool $is_in_new_tab;
    public int $rank;
    public bool $is_project_scope;
    public bool $is_link_customizable;
    public string $is_disabled_reason;

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
        bool $is_link_customizable,
        string $is_disabled_reason,
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
        $this->is_disabled_reason   = $is_disabled_reason;
    }
}
