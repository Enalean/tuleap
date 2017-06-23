<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use Tuleap\BotMattermost\Bot\Bot;
use Tuleap\BotMattermost\SenderServicesException\Exception\HasNoMessageContentException;
use Tuleap\BotMattermostGit\SenderServices\Attachment;

class EncoderMessage
{
    /**
     * @param Bot $bot
     * @param Message $message
     * @param string $channel
     * @return String [POST format]
     * @throws HasNoMessageContentException
     */
    public function generateJsonMessage(Bot $bot, Message $message, $channel)
    {
        if (! $message->hasContent()) {
            throw new HasNoMessageContentException();
        }
        $tab = array(
            "username" => $bot->getName(),
            "channel"  => strtolower($channel),
            "icon_url" => $bot->getAvatarUrl(),
        );
        if ($message->hasText()) {
            $tab["text"] = $message->getText();
        }
        if ($message->hasAttachments()) {
            $tab["attachments"] = $this->generateArrayAttachments($message->getAttachments());
        }

        return json_encode($tab);
    }

    private function generateArrayAttachments(array $attachments)
    {
        $array_attachments = array();

        foreach ($attachments as $attachment) {
            $array_attachments[] =  array(
                'color'      => $attachment->getColor(),
                'pretext'    => $attachment->getPreText(),
                'title'      => $attachment->getTitle(),
                'title_link' => $attachment->getTitleLink(),
                'text'       => $attachment->getText()
            );
        }

        return $array_attachments;
    }
}