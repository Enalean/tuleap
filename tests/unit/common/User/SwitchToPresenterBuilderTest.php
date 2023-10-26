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

use Tuleap\ForgeConfigSandbox;
use Tuleap\Layout\SearchFormPresenter;
use Tuleap\Layout\SearchFormPresenterBuilder;
use Tuleap\Project\ListOfProjectPresentersBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\AnonymousUserTestProvider;

final class SwitchToPresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    public function testNullIfUserIsNotLoggedIn(): void
    {
        $builder = new SwitchToPresenterBuilder(
            $this->createMock(ListOfProjectPresentersBuilder::class),
            $this->createMock(SearchFormPresenterBuilder::class)
        );

        self::assertNull($builder->build(CurrentUserWithLoggedInInformation::fromAnonymous(new AnonymousUserTestProvider())));
    }

    public function testBuildOfPresenter(): void
    {
        \ForgeConfig::set("access_mode", "restricted");
        \ForgeConfig::set("is_trove_cat_enabled", false);

        $user = UserTestBuilder::anActiveUser()->build();

        $project_presenters_builder = $this->createMock(ListOfProjectPresentersBuilder::class);
        $project_presenters_builder
            ->expects(self::once())
            ->method('getProjectPresenters')
            ->with($user)
            ->willReturn([]);

        $search_form_presenter_builder = $this->createMock(SearchFormPresenterBuilder::class);
        $search_form_presenter_builder
            ->expects(self::once())
            ->method('build')
            ->willReturn(new SearchFormPresenter("soft", []));

        $builder = new SwitchToPresenterBuilder($project_presenters_builder, $search_form_presenter_builder);

        $presenter = $builder->build(CurrentUserWithLoggedInInformation::fromLoggedInUser($user));

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

        $user = UserTestBuilder::aUser()->withId(101)->withStatus('D')->build();

        $project_presenters_builder = $this->createMock(ListOfProjectPresentersBuilder::class);
        $project_presenters_builder
            ->expects(self::once())
            ->method('getProjectPresenters')
            ->with($user)
            ->willReturn([]);

        $search_form_presenter_builder = $this->createMock(SearchFormPresenterBuilder::class);
        $search_form_presenter_builder
            ->expects(self::once())
            ->method('build')
            ->willReturn(new SearchFormPresenter("soft", []));

        $builder = new SwitchToPresenterBuilder($project_presenters_builder, $search_form_presenter_builder);

        $presenter = $builder->build(CurrentUserWithLoggedInInformation::fromLoggedInUser($user));

        self::assertEquals(false, $presenter->is_search_available);
    }

    public function testSearchNotAvailableIfUserIsRestricted(): void
    {
        \ForgeConfig::set("access_mode", "restricted");
        \ForgeConfig::set("is_trove_cat_enabled", false);

        $user = UserTestBuilder::aRestrictedUser()->build();

        $project_presenters_builder = $this->createMock(ListOfProjectPresentersBuilder::class);
        $project_presenters_builder
            ->expects(self::once())
            ->method('getProjectPresenters')
            ->with($user)
            ->willReturn([]);

        $search_form_presenter_builder = $this->createMock(SearchFormPresenterBuilder::class);
        $search_form_presenter_builder
            ->expects(self::once())
            ->method('build')
            ->willReturn(new SearchFormPresenter("soft", []));

        $builder = new SwitchToPresenterBuilder($project_presenters_builder, $search_form_presenter_builder);

        $presenter = $builder->build(CurrentUserWithLoggedInInformation::fromLoggedInUser($user));

        self::assertEquals(false, $presenter->is_search_available);
    }
}
