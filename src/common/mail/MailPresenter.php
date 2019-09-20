<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class MailPresenter
{

    /** @var string */
    public $platform_name;

    /** @var string */
    public $goto_link;

    /** @var string */
    public $service_shortname;

    public function __construct($service_shortname, $goto_link, $platform_name)
    {
        $this->service_shortname = $service_shortname;
        $this->goto_link         = $goto_link;

        if ($platform_name === '') {
            $platform_name = 'Tuleap';
        }

        $this->platform_name = $platform_name;
    }
}
