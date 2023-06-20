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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Config\ConfigDao;
use CSRFSynchronizerToken;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\LayoutInspectorRedirection;
use Tuleap\Test\Builders\UserTestBuilder;

final class FilesDownloadLimitsAdminSaveControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private CSRFSynchronizerToken&MockObject $token;
    private ConfigDao&MockObject $config_dao;
    private FilesDownloadLimitsAdminSaveController $controller;

    protected function setUp(): void
    {
        $this->token      = $this->createMock(CSRFSynchronizerToken::class);
        $this->config_dao = $this->createMock(ConfigDao::class);

        $this->controller = new FilesDownloadLimitsAdminSaveController($this->token, $this->config_dao);
    }

    public function testItThrowExceptionForNonSiteAdminUser(): void
    {
        $user = UserTestBuilder::aUser()->withoutSiteAdministrator()->build();

        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($user)->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItSavesTheSettings(): void
    {
        $user = UserTestBuilder::aUser()->withSiteAdministrator()->build();

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('max-archive-size', "2000")
            ->withParam('warning-threshold', "25")
            ->build();

        $this->token->expects(self::once())->method('check');

        $this->config_dao
            ->method('saveInt')
            ->withConsecutive(
                ['plugin_document_warning_threshold', 25],
                ['plugin_document_max_archive_size', 2000],
            );

        $inspector = new LayoutInspector();

        try {
            $this->controller->process(
                $request,
                LayoutBuilder::buildWithInspector($inspector),
                []
            );
        } catch (LayoutInspectorRedirection $ex) {
            self::assertEquals(new LayoutInspectorRedirection('/admin/document/files-download-limits'), $ex);
        }

        self::assertEquals(
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
        $user = UserTestBuilder::aUser()->withSiteAdministrator()->build();

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('max-archive-size', 'not-valid')
            ->withParam('warning-threshold', "25")
            ->build();

        $this->token->expects(self::once())->method('check');

        $this->config_dao->expects(self::never())
            ->method('save');

        $inspector = new LayoutInspector();

        try {
            $this->controller->process(
                $request,
                LayoutBuilder::buildWithInspector($inspector),
                []
            );
        } catch (LayoutInspectorRedirection $ex) {
            self::assertEquals(new LayoutInspectorRedirection('/admin/document/files-download-limits'), $ex);
        }
        self::assertEquals(
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
        $user = UserTestBuilder::aUser()->withSiteAdministrator()->build();

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('max-archive-size', "2000")
            ->withParam('warning-threshold', 'not-valid')
            ->build();

        $this->token->expects(self::once())->method('check');

        $this->config_dao
            ->expects(self::never())
            ->method('save');

        $inspector = new LayoutInspector();

        try {
            $this->controller->process(
                $request,
                LayoutBuilder::buildWithInspector($inspector),
                []
            );
        } catch (LayoutInspectorRedirection $ex) {
            self::assertEquals(new LayoutInspectorRedirection('/admin/document/files-download-limits'), $ex);
        }

        self::assertEquals(
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
