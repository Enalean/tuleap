<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\ProjectTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MediawikiMathExtensionEnablerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private vfsStreamDirectory $mediawiki_installation_path;
    private MediawikiExtensionDAO&MockObject $dao;
    private Mediawiki_Migration_MediawikiMigrator&MockObject $migrator;

    #[\Override]
    protected function setUp(): void
    {
        $this->mediawiki_installation_path = vfsStream::setup();
        $this->dao                         = $this->createMock(MediawikiExtensionDAO::class);
        $this->migrator                    = $this->createMock(Mediawiki_Migration_MediawikiMigrator::class);
    }

    public function testPluginCanNotBeLoadedWhenUnavailable()
    {
        $mediawiki_math_extension_enabler = new MediawikiMathExtensionEnabler($this->dao, $this->migrator);

        $is_update_running = false;
        $project           = ProjectTestBuilder::aProject()->build();

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
        $project           = ProjectTestBuilder::aProject()->build();

        $this->dao->method('isMathActivatedForProjectID')->willReturn(false);
        $this->dao->method('saveMathActivationForProjectID');
        $this->migrator->method('runUpdateScript');

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
        $project           = ProjectTestBuilder::aProject()->build();

        $this->migrator->expects($this->never())->method('runUpdateScript');
        $this->dao->expects($this->never())->method('saveMathActivationForProjectID');

        $can_plugin_be_loaded = $mediawiki_math_extension_enabler->canPluginBeLoaded(
            $this->mediawiki_installation_path->url(),
            $is_update_running,
            $project
        );

        $this->assertTrue($can_plugin_be_loaded);
    }
}
