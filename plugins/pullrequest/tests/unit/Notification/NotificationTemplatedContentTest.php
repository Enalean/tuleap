<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\Notification;

final class NotificationTemplatedContentTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testBuildsTemplatedHTML(): void
    {
        $renderer = $this->createMock(\TemplateRenderer::class);

        $expected_template_name    = 'template_name';
        $expected_presenter        = new class {
        };
        $expected_rendered_content = 'rendered_content';

        $renderer->expects(self::once())
            ->method('renderToString')
            ->with($expected_template_name, $expected_presenter)
            ->willReturn($expected_rendered_content);

        $notification_html_content = new NotificationTemplatedContent(
            $renderer,
            $expected_template_name,
            $expected_presenter
        );

        self::assertEquals($expected_rendered_content, $notification_html_content->toString());
    }
}
