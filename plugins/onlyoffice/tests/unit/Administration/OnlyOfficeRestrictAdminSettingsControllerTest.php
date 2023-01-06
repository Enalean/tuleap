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
use Tuleap\OnlyOffice\DocumentServer\IRestrictDocumentServer;
use Tuleap\OnlyOffice\Stubs\IRestrictDocumentServerStub;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class OnlyOfficeRestrictAdminSettingsControllerTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testSaveRestriction(): void
    {
        $restrictor = IRestrictDocumentServerStub::buildSelf();

        $controller = $this->buildController($restrictor);

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, UserTestBuilder::anActiveUser()->build())
            ->withAttribute('id', 1)
            ->withParsedBody(['is_restricted' => '1', 'projects' => ['101', '102']]);

        $response = $controller->handle($request);

        self::assertEquals(302, $response->getStatusCode());
        self::assertFalse($restrictor->hasBeenUnrestricted());
        self::assertTrue($restrictor->hasBeenRestricted());
    }

    public function testSaveUnestriction(): void
    {
        $restrictor = IRestrictDocumentServerStub::buildSelf();

        $controller = $this->buildController($restrictor);

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, UserTestBuilder::anActiveUser()->build())
            ->withAttribute('id', 1)
            ->withParsedBody(['is_restricted' => '0']);

        $response = $controller->handle($request);

        self::assertEquals(302, $response->getStatusCode());
        self::assertFalse($restrictor->hasBeenRestricted());
        self::assertTrue($restrictor->hasBeenUnrestricted());
    }

    public function testSaveUnestrictionFailsIfTooManyServers(): void
    {
        $restrictor = IRestrictDocumentServerStub::buildWithTooManyServersForUnrestriction();

        $controller = $this->buildController($restrictor);

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, UserTestBuilder::anActiveUser()->build())
            ->withAttribute('id', 1)
            ->withParsedBody(['is_restricted' => '0']);

        $response = $controller->handle($request);

        self::assertEquals(302, $response->getStatusCode());
        self::assertFalse($restrictor->hasBeenRestricted());
        self::assertFalse($restrictor->hasBeenUnrestricted());
    }

    /**
     * @dataProvider dataProviderInvalidSettings
     */
    public function testRejectsInvalidSettings(array $body): void
    {
        $controller = $this->buildController(IRestrictDocumentServerStub::buildSelf());

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, UserTestBuilder::anActiveUser()->build())
            ->withAttribute('id', 1)
            ->withParsedBody($body);

        $this->expectException(ForbiddenException::class);
        $controller->handle($request);
    }

    public function dataProviderInvalidSettings(): array
    {
        return [
            ['No parameters' => []],
            ['is_restricted is not in the body' => ['projects' => [1]]],
            ['Projects is not an array' => ['is_restricted' => '1', 'projects' => 'not an array']],
        ];
    }

    private function buildController(IRestrictDocumentServer $restrictor): OnlyOfficeRestrictAdminSettingsController
    {
        $csrf_token = $this->createStub(\CSRFSynchronizerToken::class);
        $csrf_token->method('check');

        $feedback_serializer = $this->createStub(FeedbackSerializer::class);
        $feedback_serializer->method('serialize');

        return new OnlyOfficeRestrictAdminSettingsController(
            $csrf_token,
            $restrictor,
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            new SapiEmitter()
        );
    }
}
