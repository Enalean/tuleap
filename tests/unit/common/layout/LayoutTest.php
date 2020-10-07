<?php
/**
 * Copyright (c) Enalean, 2011-present. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Layout\CssAsset;

/**
 * For all tests we have to use partial mock because there are sessions related stuff in Response class.
 */
//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class LayoutTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use \Tuleap\ForgeConfigSandbox;

    protected function setUp(): void
    {
        parent::setUp();
        ForgeConfig::set('sys_user_theme', 'stuff');

        $user = Mockery::spy(PFUser::class);

        $user_manager = \Mockery::mock(UserManager::class);
        $user_manager->shouldReceive(['getCurrentUser' => $user]);

        UserManager::setInstance($user_manager);
    }

    protected function tearDown(): void
    {
        UserManager::clearInstance();
    }

    public function testAddStyleSheet(): void
    {
        $l = \Mockery::mock(\Layout::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $l->addStylesheet('/theme/css/style.css');
        $this->assertEquals(['/theme/css/style.css'], $l->getAllStyleSheets());
    }

    public function testAddedStyleSheetShouldBeRenderedInPageHeaders(): void
    {
        $l = new class extends \Layout {
            public function __construct()
            {
                parent::__construct('');
            }

            public function header(array $params)
            {
            }

            protected function getEventManager()
            {
                return \Mockery::spy(EventManager::class);
            }
        };

        $css = '/vendor-css/styles.css';

        $l->addStylesheet($css);
        $l->addCssAsset(
            Mockery::mock(CssAsset::class)
                ->shouldReceive(['getFileUrl' => 'css-assets.css', 'getPath' => '/path'])
                ->getMock()
        );

        ob_start();
        $l->displayStylesheetElements([]);
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString('<link rel="stylesheet" type="text/css" href="' . $css . '" />', $content);
        $this->assertStringContainsString('<link rel="stylesheet" type="text/css" href="css-assets.css" />', $content);
    }
}
