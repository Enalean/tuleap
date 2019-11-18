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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */
declare(strict_types=1);

namespace Tuleap\Docman;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;
use PHPUnit\Framework\TestCase;

class RenameProjectTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testRenameProjectTest(): void
    {
        vfsStreamWrapper::register();
        $docman_root_directory = vfsStreamWrapper::setRoot(vfsStream::setup('docman_root', 0755));

        $old_name = 'toto';
        $new_name = 'TestProj';

        $old_directory     = vfsStream::newDirectory('toto', 0755)->at($docman_root_directory);
        $old_directory_url = $old_directory->url();

        $project = \Mockery::spy(Project::class);
        $project->allows()->getUnixName(true)->andReturns($old_name);

        $fact = \Mockery::mock(Docman_VersionFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->assertEquals(rename($old_directory->url(), $docman_root_directory->url() . '/' . $new_name), true);

        $dao = \Mockery::spy(Docman_VersionDao::class);
        $fact->allows(['_getVersionDao' => $dao]);
        $dao->allows()->renameProject($docman_root_directory->url(), $project, $new_name)->andReturns(true);

        $this->assertFalse(is_dir($old_directory_url), "Docman old rep should be renamed");
        $this->assertTrue(is_dir($docman_root_directory->url() . '/' . $new_name), "Docman new Rep should be created");
    }
}
