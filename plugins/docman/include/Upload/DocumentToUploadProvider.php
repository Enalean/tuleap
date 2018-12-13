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

use Tuleap\Docman\Tus\TusFileProvider;

final class DocumentToUploadProvider implements TusFileProvider
{
    const ONE_MB_FILE = 1048576;

    /**
     * @var string
     */
    private $root_path_storage;

    public function __construct($root_path_storage)
    {
        $this->root_path_storage = $root_path_storage;
    }

    public function getFile(\Psr\Http\Message\ServerRequestInterface $request)
    {
        $test_file = $this->root_path_storage . '/test_upload';
        if (! file_exists($test_file)) {
            touch($test_file);
        }
        $handle = fopen($test_file, 'ab');

        return new DocumentToUpload($handle, self::ONE_MB_FILE, filesize($test_file));
    }
}
