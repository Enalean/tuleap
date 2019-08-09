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
    /** @var int */
    public $rank;

    public function __construct(
        int $id,
        string $label,
        string $icon_name,
        string $link,
        string $description,
        bool $is_active,
        bool $is_used,
        bool $is_in_iframe,
        int $rank
    ) {
        $this->id           = $id;
        $this->label        = $label;
        $this->icon_name    = $icon_name;
        $this->link         = $link;
        $this->description  = $description;
        $this->is_active    = $is_active;
        $this->is_used      = $is_used;
        $this->is_in_iframe = $is_in_iframe;
        $this->rank         = $rank;
    }
}
