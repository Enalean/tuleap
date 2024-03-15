<?php
/**
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Project\Registration\Template\Upload;

final class DeleteUnusedFilesStub implements DeleteUnusedFiles
{
    private int $call_count = 0;

    private function __construct()
    {
    }

    public function deleteUnusableFile(\DateTimeImmutable $current_time): void
    {
        $this->call_count++;
    }

    public static function build(): self
    {
        return new self();
    }

    public function getCallCount(): int
    {
        return $this->call_count;
    }
}
