<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Document\Config\Admin;

use ConfigDao;
use CSRFSynchronizerToken;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tuleap\Document\Config\HistoryEnforcementSettings;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspector;

final class HistoryEnforcementAdminSaveControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var CSRFSynchronizerToken|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $token;
    /**
     * @var ConfigDao|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $config_dao;
    /**
     * @var HistoryEnforcementAdminSaveController
     */
    private $controller;

    protected function setUp(): void
    {
        $this->token      = Mockery::mock(CSRFSynchronizerToken::class);
        $this->config_dao = Mockery::mock(ConfigDao::class);

        $this->controller = new HistoryEnforcementAdminSaveController($this->token, $this->config_dao);
    }

    public function testItThrowExceptionForNonSiteAdminUser(): void
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('isSuperUser')->andReturn(false);

        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($user)->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItSavesTheSettings(): void
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('isSuperUser')->andReturn(true);

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('is-changelog-proposed-after-dnd', 1)
            ->build();

        $this->token->shouldReceive('check')->once();

        $this->config_dao
            ->shouldReceive('save')
            ->with(HistoryEnforcementSettings::IS_CHANGELOG_PROPOSED_AFTER_DND, 1)
            ->once()
            ->andReturnTrue();

        $inspector = new LayoutInspector();

        $this->controller->process(
            $request,
            LayoutBuilder::buildWithInspector($inspector),
            []
        );

        $this->assertEquals('/admin/document/history-enforcement', $inspector->getRedirectUrl());
        $this->assertEquals(
            [
                [
                    'level'   => 'info',
                    'message' => 'Settings have been saved successfully.',
                ],
            ],
            $inspector->getFeedback()
        );
    }
}
