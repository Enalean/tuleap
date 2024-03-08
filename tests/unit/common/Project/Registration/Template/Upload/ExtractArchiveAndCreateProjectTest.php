<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Registration\Template\Upload;

use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\ForgeConfigSandbox;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\Project\ImportFromArchiveStub;
use function Psl\Filesystem\create_directory;

final class ExtractArchiveAndCreateProjectTest extends TestCase
{
    use TemporaryTestDirectory;
    use ForgeConfigSandbox;

    private string $upload;

    protected function setUp(): void
    {
        $tmp = $this->getTmpDir() . '/tmp';
        create_directory($tmp);
        \ForgeConfig::set('tmp_dir', $tmp);

        $this->upload = $this->getTmpDir() . '/upload';
        create_directory($this->upload);
        \Psl\Filesystem\copy(__DIR__ . "/Tus/_fixtures/test.zip", $this->upload . '/test.zip');
    }

    public function testProcessHappyPath(): void
    {
        $logger = new TestLogger();

        $action = new ExtractArchiveAndCreateProject(
            ImportFromArchiveStub::buildWithSuccessfulImport(),
            $logger,
        );

        $action->process(1001, $this->upload . "/test.zip");

        self::assertTrue($logger->hasInfoRecords());
        self::assertFalse(\Psl\Filesystem\is_file($this->upload . "/test.zip"));
    }

    public function testProcessFailure(): void
    {
        $logger = new TestLogger();

        $action = new ExtractArchiveAndCreateProject(
            ImportFromArchiveStub::buildWithErrorDuringImport("Task failed successfully"),
            $logger,
        );

        $action->process(1001, $this->upload . "/test.zip");

        self::assertTrue($logger->hasError("Task failed successfully"));
        self::assertFalse(\Psl\Filesystem\is_file($this->upload . "/test.zip"));
    }
}
