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
    /**
     * @var string
     */
    public $file_type;

    /**
     * @var string
     */
    public $download_href;

    /**
     * @var int
     */
    public $file_size;

    private function __construct(string $file_type, string $download_href, int $file_size)
    {
        $this->file_type     = $file_type;
        $this->download_href = $download_href;
        $this->file_size     = $file_size;
    }

    public static function build(\Docman_Version $docman_version, string $download_href): self
    {
        return new self(
            $docman_version->getFiletype(),
            $download_href,
            $docman_version->getFilesize()
        );
    }
}
