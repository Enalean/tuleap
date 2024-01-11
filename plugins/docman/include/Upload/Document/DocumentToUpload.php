<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\Upload\Document;

final class DocumentToUpload
{
    public function __construct(private readonly int $item_id)
    {
    }

    public function getItemId(): int
    {
        return $this->item_id;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function getUploadHref(): string
    {
        return '/uploads/docman/file/' . urlencode((string) $this->item_id);
    }
}
