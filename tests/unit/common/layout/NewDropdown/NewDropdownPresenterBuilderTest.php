<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\layout\NewDropdown;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Project\Registration\ProjectRegistrationUserPermissionChecker;

class NewDropdownPresenterBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|EventDispatcherInterface
     */
    private $event_dispatcher;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectRegistrationUserPermissionChecker
     */
    private $project_registration;
    /**
     * @var NewDropdownPresenterBuilder
     */
    private $builder;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\PFUser
     */
    private $user;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Project
     */
    private $project;

    protected function setUp(): void
    {
        $this->event_dispatcher     = Mockery::mock(EventDispatcherInterface::class);
        $this->project_registration = Mockery::mock(ProjectRegistrationUserPermissionChecker::class);

        $this->builder = new NewDropdownPresenterBuilder($this->event_dispatcher, $this->project_registration);

        $this->user    = Mockery::mock(\PFUser::class);
        $this->project = Mockery::mock(\Project::class)->shouldReceive(['getPublicName' => 'Smartoid'])->getMock();

        \ForgeConfig::set('sys_name', 'ACME');
    }

    public function testNoSectionsWhenUserCannotCreateProject(): void
    {
        $this->project_registration
            ->shouldReceive('isUserAllowedToCreateProjects')
            ->andReturn(false);

        $presenter = $this->builder->getPresenter($this->user, null, null);

        $this->assertFalse($presenter->has_sections);
    }

    public function testGlobalSectionWhenUserCanCreateProject(): void
    {
        $this->project_registration
            ->shouldReceive('isUserAllowedToCreateProjects')
            ->andReturn(true);

        $presenter = $this->builder->getPresenter($this->user, null, null);

        $this->assertTrue($presenter->has_sections);
        $this->assertCount(1, $presenter->sections);
        $this->assertEquals('ACME', $presenter->sections[0]->label);
        $this->assertEquals('/project/new', $presenter->sections[0]->links[0]->url);
    }

    public function testNoSectionsWhenUserCannotDoStuffInProject(): void
    {
        $this->project_registration
            ->shouldReceive('isUserAllowedToCreateProjects')
            ->andReturn(false);

        $this->event_dispatcher
            ->shouldReceive('dispatch')
            ->andReturn(new NewDropdownProjectLinksCollector($this->user, $this->project, null));

        $presenter = $this->builder->getPresenter($this->user, $this->project, null);

        $this->assertFalse($presenter->has_sections);
    }

    public function testProjectSectionWhenUserCanDoStuffInProject(): void
    {
        $this->project_registration
            ->shouldReceive('isUserAllowedToCreateProjects')
            ->andReturn(false);

        $collector = new NewDropdownProjectLinksCollector($this->user, $this->project, null);
        $collector->addCurrentProjectLink(new NewDropdownLinkPresenter('/url', 'label', 'icon'));

        $this->event_dispatcher
            ->shouldReceive('dispatch')
            ->andReturn($collector);

        $presenter = $this->builder->getPresenter($this->user, $this->project, null);

        $this->assertTrue($presenter->has_sections);
        $this->assertCount(1, $presenter->sections);
        $this->assertEquals('Smartoid', $presenter->sections[0]->label);
        $this->assertEquals('/url', $presenter->sections[0]->links[0]->url);
    }

    public function testBothSectionsWhenUserCanDoStuffInProjectAndCanCreateProject(): void
    {
        $this->project_registration
            ->shouldReceive('isUserAllowedToCreateProjects')
            ->andReturn(true);

        $collector = new NewDropdownProjectLinksCollector($this->user, $this->project, null);
        $collector->addCurrentProjectLink(new NewDropdownLinkPresenter('/url', 'label', 'icon'));

        $this->event_dispatcher
            ->shouldReceive('dispatch')
            ->andReturn($collector);

        $presenter = $this->builder->getPresenter($this->user, $this->project, null);

        $this->assertTrue($presenter->has_sections);
        $this->assertCount(2, $presenter->sections);
        $this->assertEquals('Smartoid', $presenter->sections[0]->label);
        $this->assertEquals('/url', $presenter->sections[0]->links[0]->url);
        $this->assertEquals('ACME', $presenter->sections[1]->label);
        $this->assertEquals('/project/new', $presenter->sections[1]->links[0]->url);
    }

    public function testItAddsACurrentContextSection(): void
    {
        $this->project_registration
            ->shouldReceive('isUserAllowedToCreateProjects')
            ->andReturn(true);

        $collector = new NewDropdownProjectLinksCollector($this->user, $this->project, null);
        $collector->addCurrentProjectLink(new NewDropdownLinkPresenter('/url', 'label', 'icon'));

        $this->event_dispatcher
            ->shouldReceive('dispatch')
            ->andReturn($collector);

        $current_context_section = new NewDropdownLinkSectionPresenter("Current context", [
            new \Tuleap\layout\NewDropdown\NewDropdownLinkPresenter('/path/to/submit/story', 'New story', 'fa-plus')
        ]);
        $presenter = $this->builder->getPresenter($this->user, $this->project, $current_context_section);

        $this->assertTrue($presenter->has_sections);
        $this->assertCount(3, $presenter->sections);
        $this->assertEquals('Current context', $presenter->sections[0]->label);
        $this->assertEquals('/path/to/submit/story', $presenter->sections[0]->links[0]->url);
    }
}
