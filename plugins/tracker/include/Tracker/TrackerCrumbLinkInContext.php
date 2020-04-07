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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker;

use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;

class TrackerCrumbLinkInContext
{
    /**
     * @var string
     */
    private $primary_label;
    /**
     * @var string
     */
    private $secondary_label;
    /**
     * @var string
     */
    private $url;

    public function __construct(string $primary_label, string $secondary_label, string $url)
    {
        $this->primary_label   = $primary_label;
        $this->secondary_label = $secondary_label;
        $this->url             = $url;
    }

    public function getPrimaryLink(): BreadCrumbLink
    {
        return new BreadCrumbLink($this->primary_label, $this->url);
    }

    public function getSecondaryLink(): BreadCrumbLink
    {
        return new BreadCrumbLink($this->secondary_label, $this->url);
    }
}
