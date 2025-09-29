<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\Files\Upload;

/**
 * @psalm-immutable
 */
final class NewFileUpload
{
    private function __construct(
        public int $file_field_id,
        public string $file_name,
        public int $file_size,
        public string $file_type,
        public string $description,
        public int $uploading_user_id,
        public \DateTimeImmutable $expiration_date,
    ) {
    }

    public static function fromComponents(
        \Tuleap\Tracker\FormElement\Field\Files\FilesField $file_field,
        string $file_name,
        int $file_size,
        string $file_type,
        string $description,
        \PFUser $uploader,
        \DateTimeImmutable $expiration_date,
    ): self {
        return new self(
            $file_field->getId(),
            $file_name,
            $file_size,
            $file_type,
            $description,
            (int) $uploader->getId(),
            $expiration_date
        );
    }
}
