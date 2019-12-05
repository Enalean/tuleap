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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

final class NotificationTemplatedContentTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testBuildsTemplatedHTML(): void
    {
        $renderer = \Mockery::mock(\TemplateRenderer::class);

        $expected_template_name    = 'template_name';
        $expected_presenter        = new class {
        };
        $expected_rendered_content = 'rendered_content';

        $renderer->shouldReceive('renderToString')
            ->with($expected_template_name, $expected_presenter)
            ->andReturn($expected_rendered_content)
            ->once();

        $notification_html_content = new NotificationTemplatedContent(
            $renderer,
            $expected_template_name,
            $expected_presenter
        );

        $this->assertEquals($expected_rendered_content, $notification_html_content->toString());
    }
}
