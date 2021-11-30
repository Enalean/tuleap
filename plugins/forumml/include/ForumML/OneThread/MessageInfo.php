<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ForumML\OneThread;

class MessageInfo
{
    /**
     * @var AttachmentPresenter[]
     */
    private $attachments = [];
    /**
     * @var string
     */
    private $sender;
    /**
     * @var \DateTimeImmutable
     */
    private $date;
    /**
     * @var int
     */
    private $id;
    /**
     * @var string
     */
    private $body;
    /**
     * @var ?string
     */
    private $cached_html;
    /**
     * @var string
     */
    private $content_type;
    /**
     * @var string
     */
    private $msg_type;
    /**
     * @var string
     */
    private $subject;

    public function __construct(
        int $id,
        string $sender,
        string $subject,
        string $body,
        string $content_type,
        string $msg_type,
        ?string $cached_html,
        \DateTimeImmutable $date,
    ) {
        $this->id           = $id;
        $this->sender       = $sender;
        $this->date         = $date;
        $this->subject      = $subject;
        $this->body         = $body;
        $this->content_type = $content_type;
        $this->msg_type     = $msg_type;
        $this->cached_html  = $cached_html;
    }

    public function addAttachment(AttachmentPresenter $attachment): void
    {
        $this->attachments[] = $attachment;
    }

    /**
     * @return AttachmentPresenter[]
     */
    public function getAttachments(): array
    {
        return $this->attachments;
    }

    public function getSender(): string
    {
        return $this->sender;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getCachedHtml(): ?string
    {
        return $this->cached_html;
    }

    public function getContentType(): string
    {
        return $this->content_type;
    }

    public function getMsgType(): string
    {
        return $this->msg_type;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }
}
