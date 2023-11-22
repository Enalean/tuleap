<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Date\Admin;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Date\DateHelper;
use Tuleap\Date\SelectedDateDisplayPreferenceValidator;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspectorRedirection;
use Tuleap\Test\Builders\UserTestBuilder;

final class RelativeDatesDisplaySaveControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private RelativeDatesDisplaySaveController $controller;

    /**
     * @var \UserPreferencesDao&MockObject
     */
    private $preferences_dao;

    /**
     * @var \Tuleap\Config\ConfigDao&MockObject
     */
    private $config_dao;

    protected function setUp(): void
    {
        $csrf_token = $this->createMock(\CSRFSynchronizerToken::class);
        $csrf_token->method('check');
        $date_display_preference_validator = new SelectedDateDisplayPreferenceValidator();
        $this->config_dao                  = $this->createMock(\Tuleap\Config\ConfigDao::class);
        $this->preferences_dao             = $this->createMock(\UserPreferencesDao::class);

        $this->controller = new RelativeDatesDisplaySaveController(
            $csrf_token,
            $date_display_preference_validator,
            $this->config_dao,
            $this->preferences_dao
        );
    }

    public function testItSaveConfiguration(): void
    {
        $layout  = LayoutBuilder::build();
        $user    = UserTestBuilder::buildSiteAdministrator();
        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('relative-dates-display', DateHelper::PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP)
            ->build();

        $this->config_dao->expects(self::once())->method('save');
        $this->preferences_dao->expects(self::never())->method('deletePreferenceForAllUsers');

        self::expectException(LayoutInspectorRedirection::class);
        $this->controller->process($request, $layout, []);
    }

    public function testItSaveConfigurationAndOverrideAllUserPreferences(): void
    {
        $layout  = LayoutBuilder::build();
        $user    = UserTestBuilder::buildSiteAdministrator();
        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('relative-dates-display', DateHelper::PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP)
            ->withParam('relative-dates-force-preference', 'true')
            ->build();

        $this->config_dao->expects(self::once())->method('save');
        $this->preferences_dao->expects(self::once())->method('deletePreferenceForAllUsers');

        self::expectException(LayoutInspectorRedirection::class);
        $this->controller->process($request, $layout, []);
    }
}
