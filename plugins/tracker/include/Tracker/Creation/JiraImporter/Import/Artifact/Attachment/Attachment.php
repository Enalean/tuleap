<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment;

class Attachment
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $filename;

    /**
     * @var string
     */
    private $mime_type;

    /**
     * @var int
     */
    private $size;

    /**
     * @var string
     */
    private $content_url;

    public function __construct(
        int $id,
        string $filename,
        string $mime_type,
        string $content_url,
        int $size
    ) {
        $this->id          = $id;
        $this->filename    = $filename;
        $this->mime_type   = $mime_type;
        $this->size        = $size;
        $this->content_url = $content_url;
    }

    public static function buildFromIssueAPIResponse(array $issue_response): self
    {
        if (
            ! isset($issue_response['id']) ||
            ! isset($issue_response['filename']) ||
            ! isset($issue_response['mimeType']) ||
            ! isset($issue_response['created']) ||
            ! isset($issue_response['content']) ||
            ! isset($issue_response['size'])
        ) {
            throw new AttachmentIssueAPIResponseNotWellFormedException();
        }

        $id          = (int) $issue_response['id'];
        $filename    = $issue_response['filename'];
        $mime_type   = $issue_response['mimeType'];
        $content_url = $issue_response['content'];
        $size        = (int) $issue_response['size'];

        return new self(
            $id,
            $filename,
            $mime_type,
            $content_url,
            $size
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getMimeType(): string
    {
        return $this->mime_type;
    }

    public function getContentUrl(): string
    {
        return $this->content_url;
    }
}
