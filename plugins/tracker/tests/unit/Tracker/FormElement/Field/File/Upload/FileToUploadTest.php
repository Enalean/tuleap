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

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\Field\File\Upload;

use PHPUnit\Framework\TestCase;

class FileToUploadTest extends TestCase
{

    public function testGetDownloadHref()
    {
        $file = new FileToUpload(123, 'Image Pasted at 2018-9-14 15-15.png');
        $this->assertEquals(
            '/plugins/tracker/attachments/123-Image%20Pasted%20at%202018-9-14%2015-15.png',
            $file->getDownloadHref()
        );
    }
}
