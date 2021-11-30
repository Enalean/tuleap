<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository\Webhook\PostPush;

/**
 * @psalm-immutable
 */
class PostPushCommitWebhookData
{
    /**
     * @var string
     */
    private $sha1;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $message;

    /**
     * @var int
     */
    private $commit_date;

    /**
     * @var string
     */
    private $author_email;

    /**
     * @var string
     */
    private $author_name;
    /**
     * @var string
     */
    private $branch_name;

    public function __construct(
        string $sha1,
        string $title,
        string $message,
        string $branch_name,
        int $commit_date,
        string $author_email,
        string $author_name,
    ) {
        $this->sha1         = $sha1;
        $this->title        = $title;
        $this->message      = $message;
        $this->branch_name  = $branch_name;
        $this->commit_date  = $commit_date;
        $this->author_email = $author_email;
        $this->author_name  = $author_name;
    }

    public function getSha1(): string
    {
        return $this->sha1;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getCommitDate(): int
    {
        return $this->commit_date;
    }

    public function getAuthorEmail(): string
    {
        return $this->author_email;
    }

    public function getAuthorName(): string
    {
        return $this->author_name;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getBranchName(): string
    {
        return $this->branch_name;
    }
}
