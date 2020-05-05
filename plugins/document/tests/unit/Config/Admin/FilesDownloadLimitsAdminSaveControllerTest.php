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

namespace Tuleap\Document\Config\Admin;

use ConfigDao;
use CSRFSynchronizerToken;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspector;

class FilesDownloadLimitsAdminSaveControllerTest extends TestCase
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
     * @var FilesDownloadLimitsAdminSaveController
     */
    private $controller;

    protected function setUp(): void
    {
        $this->token      = Mockery::mock(CSRFSynchronizerToken::class);
        $this->config_dao = Mockery::mock(ConfigDao::class);

        $this->controller = new FilesDownloadLimitsAdminSaveController($this->token, $this->config_dao);
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
            ->withParam('max-archive-size', 2000)
            ->withParam('warning-threshold', 25)
            ->build();

        $this->token->shouldReceive('check')->once();

        $this->config_dao
            ->shouldReceive('save')
            ->with('plugin_document_max_archive_size', 2000)
            ->once()
            ->andReturnTrue();
        $this->config_dao
            ->shouldReceive('save')
            ->with('plugin_document_warning_threshold', 25)
            ->once()
            ->andReturnTrue();

        $inspector = new LayoutInspector();

        $this->controller->process(
            $request,
            LayoutBuilder::buildWithInspector($inspector),
            []
        );

        $this->assertEquals('/admin/document/files-download-limits', $inspector->getRedirectUrl());
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

    public function testItDoesNotSaveAnythingIfMaxArchiveSizeIsInvalid(): void
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('isSuperUser')->andReturn(true);

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('max-archive-size', 'not-valid')
            ->withParam('warning-threshold', 25)
            ->build();

        $this->token->shouldReceive('check')->once();

        $this->config_dao
            ->shouldReceive('save')
            ->never();

        $inspector = new LayoutInspector();

        $this->controller->process(
            $request,
            LayoutBuilder::buildWithInspector($inspector),
            []
        );

        $this->assertEquals('/admin/document/files-download-limits', $inspector->getRedirectUrl());
        $this->assertEquals(
            [
                [
                    'level'   => 'error',
                    'message' => 'Submitted maximum file size should be an unsigned integer greater than zero.',
                ],
            ],
            $inspector->getFeedback()
        );
    }

    public function testItDoesNotSaveAnythingIfWarningThresholdIsInvalid(): void
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('isSuperUser')->andReturn(true);

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('max-archive-size', 2000)
            ->withParam('warning-threshold', 'not-valid')
            ->build();

        $this->token->shouldReceive('check')->once();

        $this->config_dao
            ->shouldReceive('save')
            ->never();

        $inspector = new LayoutInspector();

        $this->controller->process(
            $request,
            LayoutBuilder::buildWithInspector($inspector),
            []
        );

        $this->assertEquals('/admin/document/files-download-limits', $inspector->getRedirectUrl());
        $this->assertEquals(
            [
                [
                    'level'   => 'error',
                    'message' => 'Submitted warning threshold should be an unsigned integer greater than zero.',
                ],
            ],
            $inspector->getFeedback()
        );
    }
}
