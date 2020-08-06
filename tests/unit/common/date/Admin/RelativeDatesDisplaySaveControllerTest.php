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

namespace Tuleap\date\Admin;

use Mockery;
use PHPUnit\Framework\TestCase;
use Tuleap\date\SelectedDateDisplayPreferenceValidator;
use Tuleap\Test\Builders\LayoutBuilder;

final class RelativeDatesDisplaySaveControllerTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var RelativeDatesDisplaySaveController
     */
    private $controller;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\UserPreferencesDao
     */
    private $preferences_dao;

    /**
     * @var \ConfigDao|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $config_dao;

    protected function setUp(): void
    {
        $csrf_token = Mockery::mock(\CSRFSynchronizerToken::class);
        $csrf_token->shouldReceive('check');
        $date_display_preference_validator = new SelectedDateDisplayPreferenceValidator();
        $this->config_dao                  = Mockery::mock(\ConfigDao::class);
        $this->preferences_dao             = Mockery::mock(\UserPreferencesDao::class);

        $this->controller = new RelativeDatesDisplaySaveController(
            $csrf_token,
            $date_display_preference_validator,
            $this->config_dao,
            $this->preferences_dao
        );
    }

    public function testItSaveConfiguration(): void
    {
        $layout = LayoutBuilder::build();
        $user   = Mockery::mock(\PFUser::class);
        $user->shouldReceive('isSuperUser')->andReturnTrue();
        $request = Mockery::mock(\HTTPRequest::class);
        $request->shouldReceive('getCurrentUser')->andReturn($user);
        $request->shouldReceive('get')->with('relative-dates-display')->andReturn(
            \DateHelper::PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP
        );
        $request->shouldReceive('get')->with('relative-dates-force-preference');

        $this->config_dao->shouldReceive('save')->once();
        $this->preferences_dao->shouldReceive('deletePreferenceForAllUsers')->never();

        $this->controller->process($request, $layout, []);
    }

    public function testItSaveConfigurationAndOverrideAllUserPreferences(): void
    {
        $layout = LayoutBuilder::build();
        $user   = Mockery::mock(\PFUser::class);
        $user->shouldReceive('isSuperUser')->andReturnTrue();
        $request = Mockery::mock(\HTTPRequest::class);
        $request->shouldReceive('getCurrentUser')->andReturn($user);
        $request->shouldReceive('get')->with('relative-dates-display')->andReturn(
            \DateHelper::PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP
        );
        $request->shouldReceive('get')->with('relative-dates-force-preference')->andReturnTrue();

        $this->config_dao->shouldReceive('save')->once();
        $this->preferences_dao->shouldReceive('deletePreferenceForAllUsers')->once();

        $this->controller->process($request, $layout, []);
    }
}
