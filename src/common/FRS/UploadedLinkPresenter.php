<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\FRS;

use Tuleap\Sanitizer\URISanitizer;
use Valid_FTPURI;
use Valid_LocalURI;

class UploadedLinkPresenter
{
    public $link;
    public $link_id;
    public $owner;
    public $name;
    public $release_time;
    public $displayed_link;

    public function __construct(UploadedLink $uploaded_link)
    {
        $this->extractLink($uploaded_link);
        $this->link_id        = $uploaded_link->getId();
        $this->owner          = $uploaded_link->getOwner()->getRealName();
        $this->name           = $uploaded_link->getName();
        $this->release_time   = date("Y-m-d", $uploaded_link->getReleaseTime());
        $this->displayed_link = ($this->name) ? $this->name : $this->getDisplayedLink();
    }

    /**
     * @return string
     */
    protected function getDisplayedLink()
    {
        if (strlen($this->link) < 50) {
            return $this->link;
        }

        return substr($this->link, 0, 23) . '...' . substr($this->link, -23);
    }

    protected function extractLink(UploadedLink $uploaded_link)
    {
        $uri_sanitizer = new URISanitizer(new Valid_LocalURI(), new Valid_FTPURI());
        $this->link    = $uri_sanitizer->sanitizeForHTMLAttribute($uploaded_link->getLink());
    }
}
