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
use Tuleap\OnlyOffice\DocumentServer\IUpdateDocumentServer;
use Tuleap\OnlyOffice\Stubs\IUpdateDocumentServerStub;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OnlyOfficeUpdateAdminSettingsControllerTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testUpdateSettings(): void
    {
        $updater = IUpdateDocumentServerStub::buildSelf();

        $controller = $this->buildController($updater);

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, UserTestBuilder::anActiveUser()->build())
            ->withParsedBody(['server_url' => 'https://example.com', 'server_key' => 'some_secret_that_is_long_enough_to_pass_the_requirement']);

        $response = $controller->handle($request);

        self::assertEquals(302, $response->getStatusCode());
        self::assertTrue($updater->hasBeenUpdated());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderInvalidSettings')]
    public function testRejectsInvalidSettings(array $body): void
    {
        $controller = $this->buildController(IUpdateDocumentServerStub::buildSelf());

        $request = (new NullServerRequest())
            ->withParsedBody($body);

        $this->expectException(ForbiddenException::class);
        $controller->handle($request);
    }

    public static function dataProviderInvalidSettings(): array
    {
        return [
            'No parameters' => [[]],
            'No server URL' => [['server_url' => '', 'server_key' => 'something']],
            'No server key' => [['server_url' => 'https://example.com', 'server_key' => '']],
            'Server key not long enough' => [['server_url' => 'https://example.com', 'server_key' => 'small']],
            'Server URL without HTTPS' => [['server_url' => 'http://example.com', 'server_key' => 'something']],
        ];
    }

    private function buildController(IUpdateDocumentServer $updater): OnlyOfficeUpdateAdminSettingsController
    {
        $csrf_token = $this->createStub(\CSRFSynchronizerToken::class);
        $csrf_token->method('check');

        $feedback_serializer = $this->createStub(FeedbackSerializer::class);
        $feedback_serializer->method('serialize');


        return new OnlyOfficeUpdateAdminSettingsController(
            $csrf_token,
            $updater,
            OnlyOfficeServerUrlValidator::buildSelf(),
            OnlyOfficeSecretKeyValidator::buildSelf(),
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            new SapiEmitter()
        );
    }
}
