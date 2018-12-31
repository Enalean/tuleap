<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Docman\Upload;

final class DocumentToUploadMaxSizeExceededException extends DocumentToUploadCreationException
{
    public function __construct($requested_size)
    {
        parent::__construct(
            'The maximum allowed size for a file is ' . formatByteToMb(\ForgeConfig::get(\ForgeConfig::get('sys_max_size_upload'))) . ', ' .
            'you requested the creation of a file of ' . formatByteToMb($requested_size)
        );
    }
}
