<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Docman\Test\Builders;

use Docman_ApprovalReviewer;

final class ApprovalReviewerTestBuilder
{
    private int $id           = 102;
    private int $state        = PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET;
    private string $comment   = '';
    private ?int $version     = null;
    private ?int $review_date = null;

    private function __construct()
    {
    }

    public static function aReviewer(): self
    {
        return new self();
    }

    public function withId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function withState(int $state): self
    {
        $this->state = $state;
        return $this;
    }

    public function withComment(string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    public function withVersion(?int $version): self
    {
        $this->version = $version;
        return $this;
    }

    public function withReviewDate(?int $review_date): self
    {
        $this->review_date = $review_date;
        return $this;
    }

    public function build(): Docman_ApprovalReviewer
    {
        $reviewer = new Docman_ApprovalReviewer();
        $reviewer->setId($this->id);
        $reviewer->setState($this->state);
        $reviewer->setComment($this->comment);
        $reviewer->setVersion($this->version);
        $reviewer->setReviewDate($this->review_date);

        return $reviewer;
    }
}
