<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\OnlyOffice\Administration;

use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Tuleap\Config\ConfigDao;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Layout\Feedback\FeedbackSerializer;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class OnlyOfficeDeleteAdminSettingsControllerTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testSaveSettings(): void
    {
        $config_dao = $this->createMock(ConfigDao::class);

        $controller = $this->buildController($config_dao);

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, UserTestBuilder::anActiveUser()->build());

        $config_dao->expects($this->atLeastOnce())->method('save');

        $response = $controller->handle($request);

        self::assertEquals(302, $response->getStatusCode());
    }

    private function buildController(ConfigDao $config_dao): OnlyOfficeDeleteAdminSettingsController
    {
        $csrf_token = $this->createStub(\CSRFSynchronizerToken::class);
        $csrf_token->method('check');

        $feedback_serializer = $this->createStub(FeedbackSerializer::class);
        $feedback_serializer->method('serialize');


        return new OnlyOfficeDeleteAdminSettingsController(
            $csrf_token,
            $config_dao,
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            new SapiEmitter()
        );
    }
}
