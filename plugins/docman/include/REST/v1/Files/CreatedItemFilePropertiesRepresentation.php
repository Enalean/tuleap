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
final class CreatedItemFilePropertiesRepresentation
{
    /**
     * @var string URL to upload the file using the tus resumable upload protocol
     *
     * @see https://tus.io/protocols/resumable-upload.html
     */
    public $upload_href;

    private function __construct(string $upload_href)
    {
        $this->upload_href = $upload_href;
    }

    public static function build(string $upload_href): self
    {
        return new self($upload_href);
    }
}
