<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

namespace Tuleap\Layout;

use Tuleap\BuildVersion\VersionPresenter;

final class SidebarPresenter
{
    public $classname;
    public $content;
    /**
     * @var VersionPresenter
     */
    public $version;
    public $copyright;
    public $has_copyright;

    public function __construct(string $classname, string $content, VersionPresenter $version)
    {
        $this->classname     = $classname;
        $this->content       = $content;
        $this->version       = $version;
        $this->has_copyright = $GLOBALS['Language']->hasText('global', 'copyright');
        $this->copyright     = '';

        if ($this->has_copyright) {
            $this->copyright = $GLOBALS['Language']->getOverridableText('global', 'copyright');
        }
    }
}
