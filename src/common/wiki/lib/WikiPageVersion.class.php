<?php
/**
 * Copyright (c) Enalean, 2015-2018. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\PHPWiki\WikiPage;

class WikiPageVersion
{

    /** @var int */
    private $page_id;

    /** @var int */
    private $version_id;

    /** @var string */
    private $content;

    public function __construct($page_id, $version_id, $content)
    {
        $this->page_id      = $page_id;
        $this->version_id   = $version_id;
        $this->content      = $content;
    }

    public function getPageId()
    {
        return $this->page_id;
    }

    public function getVersionId()
    {
        return $this->version_id;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getFormattedContent(WikiPage $page)
    {
        $formatter = new WikiPageVersionContentFormatter($page->getGid());

        return $formatter->getFormattedContent($page, $this);
    }
}
