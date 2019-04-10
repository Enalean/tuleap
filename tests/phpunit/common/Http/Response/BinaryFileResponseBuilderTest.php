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

namespace Tuleap\Http\Response;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tuleap\Http\HTTPFactoryBuilder;

final class BinaryFileResponseBuilderTest extends TestCase
{
    public function testFileResponseCanBeBuiltFromFilepath() : void
    {
        $builder = new BinaryFileResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory());

        $directory = vfsStream::setup()->url();
        $file      = $directory . '/my_file';
        file_put_contents($file, 'ABCD');

        $response = $builder->fromFilePath($file);

        $this->assertEquals('ABCD', $response->getBody()->getContents());
        $this->assertEquals(filesize($file), (int) $response->getHeaderLine('Content-Length'));
        $this->assertTrue($response->hasHeader('Content-Type'));
    }

    public function testCannotBuildResponseFromFilepathWhenTheFileDoesNotExist() : void
    {
        $builder = new BinaryFileResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory());

        $directory = vfsStream::setup()->url();
        $file      = $directory . '/my_file';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not able to read ' . $file);
        $builder->fromFilePath($file);
    }
}
