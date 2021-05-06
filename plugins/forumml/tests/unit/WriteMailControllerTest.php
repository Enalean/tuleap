<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ForumML;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspector;

class WriteMailControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItRedirectsToThreadsControllerInOrderToAvoidAnApparentlyInternalServerErrorWhenUsingTheLegacyUrl(): void
    {
        $layout_inspector = new LayoutInspector();

        $controller = new WriteMailController();
        $controller->process(
            HTTPRequestBuilder::get()->withParam('list', 123)->build(),
            LayoutBuilder::buildWithInspector($layout_inspector),
            []
        );

        self::assertEquals('/plugins/forumml/list/123/threads', $layout_inspector->getRedirectUrl());
    }
}
