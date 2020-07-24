<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\BotMattermost\SenderServices;

use Tuleap\BotMattermostGit\SenderServices\Attachment;

class Message
{

    private $text = '';
    private $attachments = array();

    public function getText()
    {
        return $this->text;
    }

    public function setText($text)
    {
        $this->text = $text;
    }

    public function hasText()
    {
        return $this->text !== '';
    }

    public function getAttachments()
    {
        return $this->attachments;
    }

    public function addAttachment(Attachment $attachment)
    {
        $this->attachments[] = $attachment;
    }

    public function hasAttachments()
    {
        return ! empty($this->attachments);
    }

    public function hasContent()
    {
        return $this->hasText() || $this->hasAttachments();
    }
}
