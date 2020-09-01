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

namespace Tuleap\User;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Layout\SearchFormPresenter;
use Tuleap\Layout\SearchFormPresenterBuilder;
use Tuleap\Project\ProjectPresentersBuilder;

class SwitchToPresenterBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    public function testNullIfUserIsNotLoggedIn(): void
    {
        $user = Mockery::mock(\PFUser::class)->shouldReceive(['isLoggedIn' => false])->getMock();

        $builder = new SwitchToPresenterBuilder(
            Mockery::mock(ProjectPresentersBuilder::class),
            Mockery::mock(SearchFormPresenterBuilder::class)
        );

        self::assertNull($builder->build($user));
    }

    public function testBuildOfPresenter(): void
    {
        \ForgeConfig::set("access_mode", "restricted");
        \ForgeConfig::set("is_trove_cat_enabled", false);

        $user = Mockery::mock(\PFUser::class)->shouldReceive(
            ['isLoggedIn' => true, 'isRestricted' => false, 'isAlive' => true]
        )->getMock();

        $project_presenters_builder = Mockery::mock(ProjectPresentersBuilder::class);
        $project_presenters_builder
            ->shouldReceive('build')
            ->with($user)
            ->once()
            ->andReturn([]);

        $search_form_presenter_builder = Mockery::mock(SearchFormPresenterBuilder::class);
        $search_form_presenter_builder
            ->shouldReceive('build')
            ->once()
            ->andReturn(new SearchFormPresenter("soft", []));

        $builder = new SwitchToPresenterBuilder($project_presenters_builder, $search_form_presenter_builder);

        $presenter = $builder->build($user);

        self::assertEquals("[]", $presenter->projects);
        self::assertEquals(true, $presenter->are_restricted_users_allowed);
        self::assertEquals(false, $presenter->is_trove_cat_enabled);
        self::assertEquals(true, $presenter->is_search_available);
        self::assertEquals('{"type_of_search":"soft","hidden_fields":[]}', $presenter->search_form);
    }

    public function testSearchNotAvailableIfUserIsNotAlive(): void
    {
        \ForgeConfig::set("access_mode", "restricted");
        \ForgeConfig::set("is_trove_cat_enabled", false);

        $user = Mockery::mock(\PFUser::class)->shouldReceive(
            ['isLoggedIn' => true, 'isRestricted' => false, 'isAlive' => false]
        )->getMock();

        $project_presenters_builder = Mockery::mock(ProjectPresentersBuilder::class);
        $project_presenters_builder
            ->shouldReceive('build')
            ->with($user)
            ->once()
            ->andReturn([]);

        $search_form_presenter_builder = Mockery::mock(SearchFormPresenterBuilder::class);
        $search_form_presenter_builder
            ->shouldReceive('build')
            ->once()
            ->andReturn(new SearchFormPresenter("soft", []));

        $builder = new SwitchToPresenterBuilder($project_presenters_builder, $search_form_presenter_builder);

        $presenter = $builder->build($user);

        self::assertEquals(false, $presenter->is_search_available);
    }

    public function testSearchNotAvailableIfUserIsRestricted(): void
    {
        \ForgeConfig::set("access_mode", "restricted");
        \ForgeConfig::set("is_trove_cat_enabled", false);

        $user = Mockery::mock(\PFUser::class)->shouldReceive(
            ['isLoggedIn' => true, 'isRestricted' => true, 'isAlive' => true]
        )->getMock();

        $project_presenters_builder = Mockery::mock(ProjectPresentersBuilder::class);
        $project_presenters_builder
            ->shouldReceive('build')
            ->with($user)
            ->once()
            ->andReturn([]);

        $search_form_presenter_builder = Mockery::mock(SearchFormPresenterBuilder::class);
        $search_form_presenter_builder
            ->shouldReceive('build')
            ->once()
            ->andReturn(new SearchFormPresenter("soft", []));

        $builder = new SwitchToPresenterBuilder($project_presenters_builder, $search_form_presenter_builder);

        $presenter = $builder->build($user);

        self::assertEquals(false, $presenter->is_search_available);
    }
}
