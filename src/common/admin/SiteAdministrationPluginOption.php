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

namespace Tuleap\Admin;

/**
 * @psalm-immutable
 */
final class SiteAdministrationPluginOption
{
    /**
     * @var string
     */
    public $label;
    /**
     * @var string
     */
    public $href;
    /**
     * @var string|null
     */
    public $shortname;

    private function __construct(string $label, string $href, ?string $shortname)
    {
        $this->label     = $label;
        $this->href      = $href;
        $this->shortname = $shortname;
    }

    public static function build(string $label, string $href): self
    {
        return new self($label, $href, null);
    }

    public static function withShortname(string $label, string $href, string $shortname): self
    {
        return new self($label, $href, $shortname);
    }
}
