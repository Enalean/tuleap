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

/**
 * For all tests we have to use partial mock because there are sessions related stuff in Respone class.
 */
//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class LayoutTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['sys_user_theme'] = 'Stuff';
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['sys_user_theme']);
        parent::tearDown();
    }

    public function testAddStyleSheet(): void
    {
        $l = \Mockery::mock(\Layout::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $l->addStylesheet('/theme/css/style.css');
        $this->assertEquals(array('/theme/css/style.css'), $l->getAllStyleSheets());
    }

    public function testAddedStyleSheetShouldBeRenderedInPageHeaders(): void
    {
        $l = \Mockery::mock(\Layout::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $l->shouldReceive('getEventManager')->andReturns(\Mockery::spy(EventManager::class));

        $css = '/vendor-css/styles.css';

        $l->addStylesheet($css);

        ob_start();
        $l->displayStylesheetElements(array());
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString('<link rel="stylesheet" type="text/css" href="' . $css . '" />', $content);
    }
}
