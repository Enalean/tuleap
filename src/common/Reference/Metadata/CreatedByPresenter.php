<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Reference\Metadata;

/**
 * @psalm-immutable
 */
final class CreatedByPresenter
{
    /**
     * @var string
     */
    public $display_name;
    /**
     * @var bool
     */
    public $has_avatar;
    /**
     * @var string
     */
    public $avatar_url;

    public function __construct(string $display_name, bool $has_avatar, string $avatar_url)
    {
        $this->display_name = $display_name;
        $this->has_avatar   = $has_avatar;
        $this->avatar_url   = $avatar_url;
    }
}
