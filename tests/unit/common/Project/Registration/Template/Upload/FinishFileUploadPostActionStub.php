<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Registration\Template\Upload;

final class FinishFileUploadPostActionStub implements FinishFileUploadPostAction
{
    private ?int $processed_project_id  = null;
    private ?string $processed_filename = null;
    private ?int $processed_user_id     = null;

    private function __construct()
    {
    }

    public static function build(): self
    {
        return new self();
    }

    #[\Override]
    public function process(int $project_id, string $filename, int $user_id): void
    {
        $this->processed_project_id = $project_id;
        $this->processed_filename   = $filename;
        $this->processed_user_id    = $user_id;
    }

    public function getProcessedFilename(): ?string
    {
        return $this->processed_filename;
    }

    public function getProcessedProjectId(): ?int
    {
        return $this->processed_project_id;
    }

    public function getProcessedUserId(): ?int
    {
        return $this->processed_user_id;
    }
}
