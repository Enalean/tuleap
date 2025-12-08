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
use Docman_ApprovalTable;
use Docman_ApprovalTableFile;
use Docman_ApprovalTableItem;

final class ApprovalTableTestBuilder
{
    private int $status         = PLUGIN_DOCMAN_APPROVAL_TABLE_DISABLED;
    private int $version_number = 1;
    /** @var list<Docman_ApprovalReviewer>  */
    private array $reviewers = [];

    private function __construct()
    {
    }

    public static function anApprovalTable(): self
    {
        return new self();
    }

    public function withStatus(int $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function withVersionNumber(int $version_number): self
    {
        $this->version_number = $version_number;
        return $this;
    }

    /**
     * @param list<Docman_ApprovalReviewer> $reviewers
     */
    public function withReviewers(array $reviewers): self
    {
        $this->reviewers = $reviewers;
        return $this;
    }

    public function build(): Docman_ApprovalTable
    {
        $table = new Docman_ApprovalTableItem();
        $table->initFromRow([
            'status' => $this->status,
        ]);
        foreach ($this->reviewers as $reviewer) {
            $table->addReviewer($reviewer);
        }

        return $table;
    }

    public function buildVersionned(): Docman_ApprovalTable
    {
        $table = new Docman_ApprovalTableFile();
        $table->initFromRow([
            'status'         => $this->status,
            'version_number' => $this->version_number,
        ]);

        return $table;
    }
}
