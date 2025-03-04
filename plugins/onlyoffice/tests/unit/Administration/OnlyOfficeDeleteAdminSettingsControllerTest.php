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
use Tuleap\ForgeConfigSandbox;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Layout\Feedback\FeedbackSerializer;
use Tuleap\OnlyOffice\DocumentServer\IDeleteDocumentServer;
use Tuleap\OnlyOffice\Stubs\IDeleteDocumentServerStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OnlyOfficeDeleteAdminSettingsControllerTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testSaveSettings(): void
    {
        $deletor = IDeleteDocumentServerStub::buildSelf();

        $controller = $this->buildController($deletor);

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, UserTestBuilder::anActiveUser()->build());

        $response = $controller->handle($request);

        self::assertEquals(302, $response->getStatusCode());
        self::assertTrue($deletor->hasBeenDeleted());
    }

    private function buildController(IDeleteDocumentServer $deletor): OnlyOfficeDeleteAdminSettingsController
    {
        $csrf_token = $this->createStub(\CSRFSynchronizerToken::class);
        $csrf_token->method('check');

        $feedback_serializer = $this->createStub(FeedbackSerializer::class);
        $feedback_serializer->method('serialize');


        return new OnlyOfficeDeleteAdminSettingsController(
            $csrf_token,
            $deletor,
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            new SapiEmitter()
        );
    }
}
