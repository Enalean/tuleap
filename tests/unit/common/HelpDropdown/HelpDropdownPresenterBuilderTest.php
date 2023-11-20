<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\HelpDropdown;

use ForgeConfig;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\REST\ExplorerEndpointAvailableEvent;
use Tuleap\Sanitizer\URISanitizer;
use Tuleap\Test\Builders\UserTestBuilder;

class HelpDropdownPresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;
    use ForgeConfigSandbox;

    /**
     * @var HelpDropdownPresenterBuilder
     */
    private $help_dropdown_builder;

    private PFUser $user;
    /**
     * @var ReleaseNoteManager&MockObject
     */
    private $release_note_manager;
    /**
     * @var URISanitizer
     */
    private $uri_sanitizer;

    protected function setUp(): void
    {
        //$this->user->shouldReceive("getPreference")->andReturn(true);

        $event_dispatcher = $this->createMock(EventDispatcherInterface::class);
        $event_dispatcher->method('dispatch')->willReturn(new ExplorerEndpointAvailableEvent());

        $this->release_note_manager = $this->createMock(ReleaseNoteManager::class);
        $this->uri_sanitizer        = new URISanitizer(new \Valid_LocalURI(), new \Valid_HTTPSURI());

        ForgeConfig::set('display_tuleap_review_link', "1");

        $this->help_dropdown_builder = new HelpDropdownPresenterBuilder(
            $this->release_note_manager,
            $event_dispatcher,
            $this->uri_sanitizer
        );
    }

    public function testBuildPresenter(): void
    {
        $user = UserTestBuilder::anActiveUser()->build();
        $user->setPreference('has_release_note_been_seen', 'true');

        $expected_result = new HelpDropdownPresenter(
            [
                HelpLinkPresenter::build(
                    'Get help',
                    '/help/',
                    'fa-life-saver',
                    $this->uri_sanitizer,
                ),
                HelpLinkPresenter::build(
                    'Documentation',
                    '/doc/en/',
                    'fa-book',
                    $this->uri_sanitizer
                ),
            ],
            null,
            HelpLinkPresenter::build(
                'Release Note',
                'https://www.tuleap.org/resources/release-notes/tuleap-11-17',
                'fa-star',
                $this->uri_sanitizer
            ),
            true,
            []
        );

        $this->release_note_manager
            ->method('getReleaseNoteLink')
            ->willReturn('https://www.tuleap.org/resources/release-notes/tuleap-11-17');

        self::assertEquals($expected_result, $this->help_dropdown_builder->build($user, '11.17'));
    }

    public function testBuildPresenterWithAnonymousUser(): void
    {
        $user = UserTestBuilder::anAnonymousUser()->build();
        $user->setPreference('has_release_note_been_seen', 'true');

        $expected_result = new HelpDropdownPresenter(
            [
                HelpLinkPresenter::build(
                    'Get help',
                    '/help/',
                    'fa-life-saver',
                    $this->uri_sanitizer
                ),
                HelpLinkPresenter::build(
                    'Documentation',
                    '/doc/en/',
                    'fa-book',
                    $this->uri_sanitizer
                ),
            ],
            null,
            HelpLinkPresenter::build(
                'Release Note',
                'https://www.tuleap.org/resources/release-notes/tuleap-11-17',
                'fa-star',
                $this->uri_sanitizer
            ),
            true,
            []
        );

        $this->release_note_manager->method('getReleaseNoteLink')
            ->willReturn('https://www.tuleap.org/resources/release-notes/tuleap-11-17');
        self::assertEquals($expected_result, $this->help_dropdown_builder->build($user, '11.17'));
    }
}
