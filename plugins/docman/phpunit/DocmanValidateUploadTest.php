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
use ForgeConfig;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ForgeConfigSandbox;

final class DocmanValidateUploadTest extends TestCase
{
    use MockeryPHPUnitIntegration, ForgeConfigSandbox;

    protected function tearDown() : void
    {
        $_FILES = [];
    }

    public function testValidFileIsAccepted() : void
    {
        $request   = Mockery::mock(Codendi_Request::class);
        $request->shouldReceive('exist')->with('upload_content')->andReturn(false);

        ForgeConfig::set(PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING, '10000');

        $_FILES = [];
        $_FILES['file'] = ['name' => 'my_file', 'size' => 100, 'error' => UPLOAD_ERR_OK];

        $validator = new Docman_ValidateUpload($request);
        $this->assertTrue($validator->isValid());
    }

    public function testTooLargeFileIsRejected() : void
    {
        $request   = Mockery::mock(Codendi_Request::class);
        $request->shouldReceive('exist')->with('upload_content')->andReturn(false);

        ForgeConfig::set(PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING, '10');

        $_FILES = [];
        $_FILES['file'] = ['name' => 'my_file', 'size' => 100, 'error' => UPLOAD_ERR_OK];

        $validator = new Docman_ValidateUpload($request);
        $this->assertFalse($validator->isValid());
    }
}
