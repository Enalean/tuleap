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
use Tuleap\REST\ExplorerEndpointAvailableEvent;

class HelpDropdownPresenterBuilderTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var HelpDropdownPresenterBuilder
     */
    private $help_dropdown_builder;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;

    protected function setUp(): void
    {
        $this->user = Mockery::mock(PFUser::class);
        $this->user->shouldReceive("getShortLocale")->andReturn('en');

        $event_dispatcher = Mockery::mock(EventDispatcherInterface::class);
        $event_dispatcher->shouldReceive('dispatch')->andReturn(
            new ExplorerEndpointAvailableEvent()
        );

        $this->help_dropdown_builder = new HelpDropdownPresenterBuilder($event_dispatcher);
    }

    public function testBuildPresenterWithLabMod(): void
    {
        $this->user->shouldReceive('useLabFeatures')->andReturn(true);

        $expected_result = new HelpDropdownPresenter(
            [
                new HelpLinkPresenter(
                    'Get help',
                    "/help/",
                    "fa-life-saver"
                ),
                new HelpLinkPresenter(
                    'Documentation',
                    "/doc/en/",
                    "fa-book"
                )
            ],
            null,
            new HelpLinkPresenter(
                'Release Note',
                'https://www.tuleap.org/ressources/release-notes/tuleap-11-17',
                "fa-star"
            )
        );

        $this->assertEquals($expected_result, $this->help_dropdown_builder->build($this->user, "11.17.99.234"));
    }

    public function testBuildPresenterWithoutLabMod(): void
    {
        $this->user->shouldReceive('useLabFeatures')->andReturn(false);

        $expected_result = new HelpDropdownPresenter(
            [
                new HelpLinkPresenter(
                    'Get help',
                    "/help/",
                    "fa-life-saver"
                ),
                new HelpLinkPresenter(
                    'Documentation',
                    "/doc/en/",
                    "fa-book"
                )
            ],
            null,
            null
        );

        $this->assertEquals($expected_result, $this->help_dropdown_builder->build($this->user, "11.17.99.234"));
    }
}
