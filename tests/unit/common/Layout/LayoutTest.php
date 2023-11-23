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

namespace Tuleap\Layout;

use ForgeConfig;
use Tuleap\Test\Builders\UserTestBuilder;
use UserManager;

/**
 * For all tests we have to use partial mock because there are sessions related stuff in Response class.
 */
class LayoutTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Tuleap\ForgeConfigSandbox;

    protected function setUp(): void
    {
        parent::setUp();
        ForgeConfig::set('sys_user_theme', 'stuff');

        $user = UserTestBuilder::anActiveUser()->build();

        $user_manager = $this->createMock(UserManager::class);
        $user_manager->method('getCurrentUser')->willReturn($user);

        UserManager::setInstance($user_manager);
    }

    protected function tearDown(): void
    {
        UserManager::clearInstance();
    }

    public function testAddStyleSheet(): void
    {
        $l = new class extends \Layout {
            public function __construct()
            {
                parent::__construct('');
            }

            public function header(array|HeaderConfiguration $params): void
            {
            }

            protected function hasHeaderBeenWritten(): bool
            {
                return false;
            }

            public function footer(FooterConfiguration|array $params): void
            {
            }
        };
        $l->addStylesheet('/theme/css/style.css');
        self::assertEquals(['/theme/css/style.css'], $l->getAllStyleSheets());
    }

    public function testAddedStyleSheetShouldBeRenderedInPageHeaders(): void
    {
        $l             = new class extends \Layout {
            public function __construct()
            {
                parent::__construct('');
            }

            public function header(array|HeaderConfiguration $params): void
            {
            }

            public function footer(FooterConfiguration|array $params): void
            {
            }

            public function hasHeaderBeenWritten(): bool
            {
                return false;
            }

            private \EventManager $event_manager;

            protected function getEventManager(): \EventManager
            {
                return $this->event_manager;
            }

            public function setEventManager(\EventManager $event_manager): void
            {
                $this->event_manager = $event_manager;
            }
        };
        $event_manager = $this->createMock(\EventManager::class);
        $l->setEventManager($event_manager);
        $event_manager->method('processEvent');

        $css = '/vendor-css/styles.css';

        $l->addStylesheet($css);
        $l->addCssAsset(
            new class implements \Tuleap\Layout\CssAssetGeneric {
                public function getFileURL(\Tuleap\Layout\ThemeVariation $variant): string
                {
                    return 'css-assets.css';
                }

                public function getIdentifier(): string
                {
                    return '/path';
                }
            }
        );

        ob_start();
        $l->displayStylesheetElements([]);
        $content = ob_get_contents();
        ob_end_clean();

        self::assertStringContainsString('<link rel="stylesheet" type="text/css" href="' . $css . '" />', $content);
        self::assertStringContainsString('<link rel="stylesheet" type="text/css" href="css-assets.css" />', $content);
    }
}
