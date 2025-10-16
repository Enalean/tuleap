<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\OpenGraph;

class OpenGraphPresenter
{
    public $properties = [];

    public function __construct($url, $title, $description)
    {
        if ($url) {
            $this->properties[] = new OpenGraphPropertyPresenter('url', $url);
        }
        if ($title) {
            $this->properties[] = new OpenGraphPropertyPresenter('title', $title);
        }
        if ($description) {
            $this->properties[] = new OpenGraphPropertyPresenter('description', $description);
        }

        $this->properties[] = new OpenGraphPropertyPresenter(
            'image',
            \Tuleap\ServerHostname::HTTPSUrl() . '/themes/common/images/opengraph/tuleap-logo.png'
        );
    }
}
