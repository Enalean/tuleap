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

class IncomingMail
{
    /**
     * @var \PhpMimeMailParser\Parser
     */
    private $parser;

    public function __construct($incoming_stream_mail)
    {
        $this->parser = new \PhpMimeMailParser\Parser();
        $this->parser->setStream($incoming_stream_mail);
    }

    public function getHeaders()
    {
        return $this->parser->getHeaders();
    }

    /**
     * @return IncomingMailBody
     */
    public function getBody()
    {
        $body = $this->parser->getMessageBody('html');
        if ($body !== '') {
            return new IncomingMailBodyHTML($body);
        }
        $body = $this->parser->getMessageBody('text');
        return new IncomingMailBodyText($body);
    }

    /**
     * @return IncomingAttachment[]
     */
    public function getAttachments()
    {
        $attachments = [];
        foreach ($this->parser->getAttachments() as $attachment) {
            $attachments[] = new IncomingAttachment($attachment);
        }
        return $attachments;
    }
}
