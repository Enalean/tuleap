<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1\Files;

/**
 * @psalm-immutable
 */
class FilePropertiesRepresentation
{
    private function __construct(
        public string $file_type,
        public string $download_href,
        public ?string $open_href,
        public int $file_size,
        public string $file_name,
    ) {
    }

    public static function build(\Docman_Version $docman_version, string $download_href, ?string $open_href): self
    {
        return new self(
            $docman_version->getFiletype(),
            $download_href,
            $open_href,
            $docman_version->getFilesize(),
            $docman_version->getFilename(),
        );
    }
}
