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

final class SearchFileUploadIdsStub implements SearchFileUploadIds
{
    private function __construct(private readonly array $file_upload_ids)
    {
    }

    public function searchFileOngoingUploadIds(): array
    {
        return $this->file_upload_ids;
    }

    public static function withFileIds(array $file_upload_ids): self
    {
        return new self($file_upload_ids);
    }
}
