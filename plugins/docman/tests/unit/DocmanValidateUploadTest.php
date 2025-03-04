<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Docman;

use Codendi_Request;
use Docman_ValidateUpload;
use DocmanPlugin;
use ForgeConfig;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DocmanValidateUploadTest extends TestCase
{
    use ForgeConfigSandbox;

    protected function tearDown(): void
    {
        $_FILES = [];
    }

    public function testValidFileIsAccepted(): void
    {
        $request = new Codendi_Request([]);

        ForgeConfig::set(DocmanPlugin::PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING, '10000');

        $_FILES         = [];
        $_FILES['file'] = ['name' => 'my_file', 'size' => 100, 'error' => UPLOAD_ERR_OK];

        $validator = new Docman_ValidateUpload($request);
        self::assertTrue($validator->isValid());
    }

    public function testTooLargeFileIsRejected(): void
    {
        $request = new Codendi_Request([]);

        ForgeConfig::set(DocmanPlugin::PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING, '10');

        $_FILES         = [];
        $_FILES['file'] = ['name' => 'my_file', 'size' => 100, 'error' => UPLOAD_ERR_OK];

        $validator = new Docman_ValidateUpload($request);
        self::assertFalse($validator->isValid());
    }
}
