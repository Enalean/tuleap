<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
 * Copyright (c) The Codendi Team, Xerox, 2009. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 *
 */

declare(strict_types=1);

namespace Tuleap\Backend;

use Backend;
use EventManager;
use org\bovigo\vfs\vfsStream;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class BackendTest extends \Tuleap\Test\PHPUnit\TestCase
{
    #[\Override]
    protected function tearDown(): void
    {
        Backend::clearInstances();
        EventManager::clearInstance();
    }

    public function testFactoryCore(): void
    {
        // Core backends
        self::assertInstanceOf(\BackendSVN::class, Backend::instance(Backend::SVN));
        self::assertInstanceOf(\Backend::class, Backend::instance(Backend::BACKEND));
        self::assertInstanceOf(\BackendSystem::class, Backend::instance(Backend::SYSTEM));
        self::assertInstanceOf(\BackendAliases::class, Backend::instance(Backend::ALIASES));
    }

    public function testFactoryPlugin(): void
    {
        $fake_backend = new class extends Backend
        {
            public function __construct()
            {
            }
        };
        //Plugin backends. Give the base classname to build the backend
        self::assertInstanceOf($fake_backend::class, Backend::instance('plugin_fake', $fake_backend::class)); //like plugins !
    }

    public function testFactoryPluginBad(): void
    {
        //The base classname is mandatory for unkown (by core) backends
        // else it search for Backend . $type
        $this->expectException(\RuntimeException::class);
        Backend::instance('plugin_fake');
    }

    public function testRecurseDeleteInDir(): void
    {
        $test_dir = vfsStream::setup()->url();

        // Create dummy dirs and files
        mkdir($test_dir . '/test1');
        mkdir($test_dir . '/test1/A');
        mkdir($test_dir . '/test1/B');
        mkdir($test_dir . '/test2');
        mkdir($test_dir . '/test2/A');
        mkdir($test_dir . '/test3');

        // Run tested method
        Backend::instance()->recurseDeleteInDir($test_dir);

        // Check result

        // Direcory should not be removed
        self::assertDirectoryExists($test_dir);
        // And should be empty
        $d = opendir($test_dir);
        while (($file = readdir($d)) !== false) {
            self::assertTrue($file === '.' || $file === '..', 'Directory should be empty');
        }
        closedir($d);
        rmdir($test_dir);
    }
}
