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

namespace Tuleap\Mediawiki;

use Mediawiki_Migration_MediawikiMigrator;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class MediawikiMathExtensionEnablerTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var \org\bovigo\vfs\vfsStreamDirectory
     */
    private $mediawiki_installation_path;
    /**
     * @var \Mockery\MockInterface
     */
    private $dao;
    /**
     * @var \Mockery\MockInterface
     */
    private $migrator;

    protected function setUp(): void
    {
        $this->mediawiki_installation_path = vfsStream::setup();
        $this->dao                         = \Mockery::mock(MediawikiExtensionDAO::class);
        $this->migrator                    = \Mockery::mock(Mediawiki_Migration_MediawikiMigrator::class);
    }

    public function testPluginCanNotBeLoadedWhenUnavailable()
    {
        $mediawiki_math_extension_enabler = new MediawikiMathExtensionEnabler($this->dao, $this->migrator);

        $is_update_running = false;
        $project           = \Mockery::mock(\Project::class);

        $can_plugin_be_loaded = $mediawiki_math_extension_enabler->canPluginBeLoaded(
            $this->mediawiki_installation_path->url(),
            $is_update_running,
            $project
        );

        $this->assertFalse($can_plugin_be_loaded);
    }

    public function testPluginIsActivatedTheFirstTimeItCanBeLoaded()
    {
        $mediawiki_math_extension_enabler = new MediawikiMathExtensionEnabler($this->dao, $this->migrator);

        $plugin_hook = $this->mediawiki_installation_path->url() . MediawikiMathExtensionEnabler::PLUGIN_MAIN_HOOK_FILE;
        mkdir($plugin_hook, 0777, true);
        touch($plugin_hook);

        $is_update_running = false;
        $project           = \Mockery::mock(\Project::class);

        $project->shouldReceive('getID');
        $this->dao->shouldReceive('isMathActivatedForProjectID')->andReturn(false);
        $this->dao->shouldReceive('saveMathActivationForProjectID');
        $this->migrator->shouldReceive('runUpdateScript');

        $can_plugin_be_loaded = $mediawiki_math_extension_enabler->canPluginBeLoaded(
            $this->mediawiki_installation_path->url(),
            $is_update_running,
            $project
        );

        $this->assertTrue($can_plugin_be_loaded);
    }

    public function testPluginCanBeLoadedWhenAnUpgradeIsOngoing()
    {
        $mediawiki_math_extension_enabler = new MediawikiMathExtensionEnabler($this->dao, $this->migrator);

        $plugin_hook = $this->mediawiki_installation_path->url() . MediawikiMathExtensionEnabler::PLUGIN_MAIN_HOOK_FILE;
        mkdir($plugin_hook, 0777, true);
        touch($plugin_hook);

        $is_update_running = true;
        $project           = \Mockery::mock(\Project::class);

        $this->migrator->shouldReceive('runUpdateScript')->never();
        $this->dao->shouldReceive('saveMathActivationForProjectID')->never();

        $can_plugin_be_loaded = $mediawiki_math_extension_enabler->canPluginBeLoaded(
            $this->mediawiki_installation_path->url(),
            $is_update_running,
            $project
        );

        $this->assertTrue($can_plugin_be_loaded);
    }
}
