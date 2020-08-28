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

use Mockery;
use PFUser;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\GlobalLanguageMock;
use Tuleap\REST\ExplorerEndpointAvailableEvent;
use Tuleap\Sanitizer\URISanitizer;

class HelpDropdownPresenterBuilderTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var HelpDropdownPresenterBuilder
     */
    private $help_dropdown_builder;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ReleaseNoteManager
     */
    private $release_note_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|URISanitizer
     */
    private $uri_sanitizer;

    protected function setUp(): void
    {
        $this->user = Mockery::mock(PFUser::class);
        $this->user->shouldReceive("getShortLocale")->andReturn('en');
        $this->user->shouldReceive("getPreference")->andReturn(true);

        $event_dispatcher = Mockery::mock(EventDispatcherInterface::class);
        $event_dispatcher->shouldReceive('dispatch')->andReturn(
            new ExplorerEndpointAvailableEvent()
        );

        $this->release_note_manager = Mockery::mock(ReleaseNoteManager::class);
        $this->uri_sanitizer        = Mockery::mock(URISanitizer::class);

        $this->uri_sanitizer
            ->shouldReceive('sanitizeForHTMLAttribute')
            ->withArgs(["/help/"])
            ->andReturn("/help/");

        $this->uri_sanitizer
            ->shouldReceive('sanitizeForHTMLAttribute')
            ->withArgs(["/doc/en/"])
            ->andReturn("/doc/en/");

        $this->help_dropdown_builder = new HelpDropdownPresenterBuilder(
            $this->release_note_manager,
            $event_dispatcher,
            $this->uri_sanitizer
        );
    }

    public function testBuildPresenter(): void
    {
        $this->user->shouldReceive('isAnonymous')->andReturn(false);

        $this->uri_sanitizer
            ->shouldReceive('sanitizeForHTMLAttribute')
            ->withArgs(["https://www.tuleap.org/resources/release-notes/tuleap-11-17"])
            ->andReturn("https://www.tuleap.org/resources/release-notes/tuleap-11-17");

        $expected_result = new HelpDropdownPresenter(
            [
                HelpLinkPresenter::build(
                    'Get help',
                    "/help/",
                    "fa-life-saver",
                    $this->uri_sanitizer,
                ),
                HelpLinkPresenter::build(
                    'Documentation',
                    "/doc/en/",
                    "fa-book",
                    $this->uri_sanitizer
                )
            ],
            null,
            HelpLinkPresenter::build(
                'Release Note',
                'https://www.tuleap.org/resources/release-notes/tuleap-11-17',
                "fa-star",
                $this->uri_sanitizer
            ),
            true,
            []
        );

        $this->release_note_manager
            ->shouldReceive('getReleaseNoteLink')
            ->andReturn("https://www.tuleap.org/resources/release-notes/tuleap-11-17");

        $this->assertEquals($expected_result, $this->help_dropdown_builder->build($this->user, "11.17"));
    }

    public function testBuildPresenterWithAnonymousUser(): void
    {
        $this->user->shouldReceive('isAnonymous')->andReturn(true);

        $this->uri_sanitizer
            ->shouldReceive('sanitizeForHTMLAttribute')
            ->withArgs(["https://www.tuleap.org/resources/release-notes/tuleap-11-17"])
            ->andReturn("https://www.tuleap.org/resources/release-notes/tuleap-11-17");

        $expected_result = new HelpDropdownPresenter(
            [
                HelpLinkPresenter::build(
                    'Get help',
                    "/help/",
                    "fa-life-saver",
                    $this->uri_sanitizer
                ),
                HelpLinkPresenter::build(
                    'Documentation',
                    "/doc/en/",
                    "fa-book",
                    $this->uri_sanitizer
                )
            ],
            null,
            HelpLinkPresenter::build(
                'Release Note',
                'https://www.tuleap.org/resources/release-notes/tuleap-11-17',
                "fa-star",
                $this->uri_sanitizer
            ),
            true,
            []
        );

        $this->release_note_manager->shouldReceive('getReleaseNoteLink')->andReturn(
            "https://www.tuleap.org/resources/release-notes/tuleap-11-17"
        );
        $this->assertEquals($expected_result, $this->help_dropdown_builder->build($this->user, "11.17"));
    }
}
