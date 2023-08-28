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

use BackendSVN;
use Event;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use Tuleap\GlobalSVNPollution;
use Backend;
use EventManager;

final class BackendTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalSVNPollution;

    protected function tearDown(): void
    {
        Backend::clearInstances();
        EventManager::clearInstance();
    }

    public function testFactoryCore(): void
    {
        // Core backends
        $this->assertInstanceOf(\BackendSVN::class, Backend::instance(Backend::SVN));
        $this->assertInstanceOf(\Backend::class, Backend::instance(Backend::BACKEND));
        $this->assertInstanceOf(\BackendSystem::class, Backend::instance(Backend::SYSTEM));
        $this->assertInstanceOf(\BackendAliases::class, Backend::instance(Backend::ALIASES));
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
        $this->assertInstanceOf($fake_backend::class, Backend::instance('plugin_fake', $fake_backend::class)); //like plugins !
    }

    public function testFactoryPluginBad(): void
    {
        //The base classname is mandatory for unkown (by core) backends
        // else it search for Backend . $type
        $this->expectException(\RuntimeException::class);
        Backend::instance('plugin_fake');
    }

    public function testFactoryOverride(): void
    {
        //Create a fake backend class which simulate an override of BackendSVN by a plugin
        $backend_overridden_by_plugin = new class extends BackendSVN
        {
            public function __construct()
            {
            }
        };
        //Plugins can override default backends.
        // For example, plugin_ldap can override the backend define in plugin_svn
        EventManager::instance()->addListener(
            Event::BACKEND_FACTORY_GET_SVN,
            $this->buildPluginTestBackend($backend_overridden_by_plugin),
            'getBackend',
            false
        );
        $this->assertInstanceOf($backend_overridden_by_plugin::class, Backend::instance(Backend::SVN));
    }

    public function testFactoryOverrideWithoutParameters(): void
    {
        $backend_overridden_by_plugin_and_has_setup = new class extends BackendSVN
        {
            public $a_variable_for_tests = -25;

            public function __construct()
            {
            }

            protected function setUp($a, $b, $c): void
            {
                $this->a_variable_for_tests = ($a + $b) * $c;
            }
        };
        //Plugins can override default backends.
        // For example, plugin_ldap can override the backend define in plugin_svn
        EventManager::instance()->addListener(
            Event::BACKEND_FACTORY_GET_SVN,
            $this->buildPluginTestBackend($backend_overridden_by_plugin_and_has_setup),
            'getBackend',
            false
        );
        $b = Backend::instance(Backend::SVN);
        $this->assertEquals($b->a_variable_for_tests, -25);
    }

    public function testFactoryOverrideWithParameters(): void
    {
        $backend_overridden_by_plugin_and_has_setup = new class extends BackendSVN
        {
            public $a_variable_for_tests = -25;

            public function __construct()
            {
            }

            protected function setUp($a, $b, $c): void
            {
                $this->a_variable_for_tests = ($a + $b) * $c;
            }
        };
        //Plugins can override default backends.
        // For example, plugin_ldap can override the backend define in plugin_svn
        EventManager::instance()->addListener(
            Event::BACKEND_FACTORY_GET_SVN,
            $this->buildPluginTestBackend($backend_overridden_by_plugin_and_has_setup),
            'getBackend',
            false
        );
        $b = Backend::instance(Backend::SVN, null, [1, 2, 3]);
        $this->assertEquals($b->a_variable_for_tests, 9);
    }

    public function testFactoryOverrideWithParametersDefinedInPlugin(): void
    {
        $backend_overridden_by_plugin_and_has_setup = new class extends BackendSVN
        {
            public $a_variable_for_tests = -25;

            public function __construct()
            {
            }

            protected function setUp($a, $b, $c): void
            {
                $this->a_variable_for_tests = ($a + $b) * $c;
            }
        };
        $backend_plugins_with_setup_and_params      = new class ($backend_overridden_by_plugin_and_has_setup)
        {
            /**
             * @var BackendSVN
             */
            private $test_override_backend;

            public function __construct(BackendSVN $test_override_backend)
            {
                $this->test_override_backend = $test_override_backend;
            }

            public function getBackend(array $params): void
            {
                $params['base']  = get_class($this->test_override_backend);
                $params['setup'] = [1, 2, 3];
            }
        };
        //Plugins can override default backends.
        // For example, plugin_ldap can override the backend define in plugin_svn
        EventManager::instance()->addListener(
            Event::BACKEND_FACTORY_GET_SVN,
            $backend_plugins_with_setup_and_params,
            'getBackend',
            false
        );
        $b = Backend::instance(Backend::SVN);
        $this->assertEquals($b->a_variable_for_tests, 9);
    }

    public function testFactoryOverrideWithParametersButNoSetUp(): void
    {
        $test_backend = new class extends BackendSVN
        {
            public function __construct()
            {
            }
        };
        //Plugins can override default backends.
        // For example, plugin_ldap can override the backend define in plugin_svn
        EventManager::instance()->addListener(
            Event::BACKEND_FACTORY_GET_SVN,
            $this->buildPluginTestBackend($test_backend),         //no setup !!!
            'getBackend',
            false
        );
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('does not have setUp');
        Backend::instance(Backend::SVN, null, [1, 2, 3]);
    }

    public function testRecurseDeleteInDir(): void
    {
        $test_dir = vfsStream::setup()->url();

        // Create dummy dirs and files
        mkdir($test_dir . "/test1");
        mkdir($test_dir . "/test1/A");
        mkdir($test_dir . "/test1/B");
        mkdir($test_dir . "/test2");
        mkdir($test_dir . "/test2/A");
        mkdir($test_dir . "/test3");

        // Run tested method
        Backend::instance()->recurseDeleteInDir($test_dir);

        // Check result

        // Direcory should not be removed
        $this->assertDirectoryExists($test_dir);
        // And should be empty
        $d = opendir($test_dir);
        while (($file = readdir($d)) !== false) {
            $this->assertTrue($file === '.' || $file === '..', 'Directory should be empty');
        }
        closedir($d);
        rmdir($test_dir);
    }

    private function buildPluginTestBackend(BackendSVN $backend): object
    {
        return new class ($backend) {
            /**
             * @var BackendSVN
             */
            private $test_override_backend;

            public function __construct(BackendSVN $test_override_backend)
            {
                $this->test_override_backend = $test_override_backend;
            }

            public function getBackend(array $params): void
            {
                $params['base'] = get_class($this->test_override_backend);
            }
        };
    }
}
