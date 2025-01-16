<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Upload\Section\File;

use Tuleap\Tus\Identifier\FileIdentifier;

interface SearchFileUpload
{
    /**
     * @return array{id: FileIdentifier, file_size: int, file_name: string, user_id: int, expiration_date: int, item_id: int} | null
     */
    public function searchFileOngoingUploadById(FileIdentifier $id): ?array;
}
