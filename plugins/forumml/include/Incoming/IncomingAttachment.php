<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\ForumML\Incoming;

use PhpMimeMailParser\Attachment;

class IncomingAttachment
{
    /**
     * @var Attachment
     */
    private $attachment;

    public function __construct(Attachment $attachment)
    {
        $this->attachment = $attachment;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return basename($this->attachment->getFilename());
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->attachment->getContent();
    }

    /**
     * @return string
     */
    public function getContentID()
    {
        $content_id = $this->attachment->getContentID();
        if ($content_id === false) {
            return '';
        }
        return '<' . $content_id . '>';
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return $this->attachment->getContentType();
    }
}
