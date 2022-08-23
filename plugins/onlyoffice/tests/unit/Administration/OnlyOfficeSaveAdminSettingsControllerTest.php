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
use org\bovigo\vfs\vfsStream;
use Tuleap\Config\ConfigDao;
use Tuleap\Config\ConfigSet;
use Tuleap\Config\GetConfigKeys;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Layout\Feedback\FeedbackSerializer;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stub\EventDispatcherStub;
use Valid_HTTPSURI;

final class OnlyOfficeSaveAdminSettingsControllerTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testSaveSettings(): void
    {
        $config_dao = $this->createMock(ConfigDao::class);

        $controller = $this->buildController($config_dao);

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, UserTestBuilder::anActiveUser()->build())
            ->withParsedBody(['server_url' => 'https://example.com', 'server_key' => 'some_secret_that_is_long_enough_to_pass_the_requirement']);

        $config_dao->expects($this->atLeastOnce())->method('save');

        $response = $controller->handle($request);

        self::assertEquals(302, $response->getStatusCode());
    }

    /**
     * @dataProvider dataProviderInvalidSettings
     */
    public function testRejectsInvalidSettings(array $body): void
    {
        $controller = $this->buildController($this->createStub(ConfigDao::class));

        $request = (new NullServerRequest())
            ->withParsedBody($body);

        $this->expectException(ForbiddenException::class);
        $controller->handle($request);
    }

    public function dataProviderInvalidSettings(): array
    {
        return [
            ['No parameters' => []],
            ['No server URL' => ['server_url' => '', 'server_key' => 'something']],
            ['No server key' => ['server_url' => 'https://example.com', 'server_key' => '']],
            ['Server key not long enough' => ['server_url' => 'https://example.com', 'server_key' => 'small']],
            ['Server URL without HTTPS' => ['server_url' => 'http://example.com', 'server_key' => 'something']],
        ];
    }

    private function buildController(ConfigDao $config_dao): OnlyOfficeSaveAdminSettingsController
    {
        $csrf_token = $this->createStub(\CSRFSynchronizerToken::class);
        $csrf_token->method('check');

        $event_dispatcher = EventDispatcherStub::withCallback(
            function (GetConfigKeys $event): GetConfigKeys {
                $event->addConfigClass(OnlyOfficeDocumentServerSettings::class);
                return $event;
            }
        );

        $feedback_serializer = $this->createStub(FeedbackSerializer::class);
        $feedback_serializer->method('serialize');

        $root = vfsStream::setup()->url();
        \ForgeConfig::set('sys_custom_dir', $root);
        mkdir($root . '/conf/');

        return new OnlyOfficeSaveAdminSettingsController(
            $csrf_token,
            new ConfigSet($event_dispatcher, $config_dao),
            new Valid_HTTPSURI(),
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            new SapiEmitter()
        );
    }
}
