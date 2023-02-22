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

namespace Tuleap\FullTextSearchMeilisearch\Server\Administration;

use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use org\bovigo\vfs\vfsStream;
use Tuleap\Config\ConfigDao;
use Tuleap\Config\ConfigSet;
use Tuleap\Config\GetConfigKeys;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\ForgeConfigSandbox;
use Tuleap\FullTextSearchMeilisearch\Server\IProvideCurrentKeyForLocalServer;
use Tuleap\FullTextSearchMeilisearch\Server\MeilisearchAPIKeyValidator;
use Tuleap\FullTextSearchMeilisearch\Server\MeilisearchIndexNameValidator;
use Tuleap\FullTextSearchMeilisearch\Server\MeilisearchServerURLValidator;
use Tuleap\FullTextSearchMeilisearch\Server\RemoteMeilisearchServerSettings;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Layout\Feedback\FeedbackSerializer;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;

final class MeilisearchSaveAdminSettingsControllerTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testSaveSettings(): void
    {
        $config_dao = $this->createMock(ConfigDao::class);

        $controller = $this->buildController($config_dao, false);

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, UserTestBuilder::anActiveUser()->build())
            ->withParsedBody(
                ['server_url' => 'https://example.com', 'api_key' => 'some_secret', 'index_name' => 'fts_tuleap']
            );

        $config_dao->expects($this->atLeastOnce())->method('save');

        $response = $controller->handle($request);

        self::assertEquals(302, $response->getStatusCode());
    }

    public function testLocalServerDontHaveSettingsPage(): void
    {
        $config_dao = $this->createMock(ConfigDao::class);

        $controller = $this->buildController($config_dao, true);

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, UserTestBuilder::anActiveUser()->build())
            ->withParsedBody(
                ['server_url' => 'https://example.com', 'api_key' => 'some_secret', 'index_name' => 'fts_tuleap']
            );

        $config_dao->expects($this->never())->method('save');

        $this->expectException(ForbiddenException::class);
        $response = $controller->handle($request);
    }

    /**
     * @dataProvider dataProviderInvalidSettings
     */
    public function testRejectsInvalidSettings(array $body): void
    {
        $controller = $this->buildController($this->createStub(ConfigDao::class), false);

        $request = (new NullServerRequest())
            ->withParsedBody($body);

        $this->expectException(ForbiddenException::class);
        $controller->handle($request);
    }

    public static function dataProviderInvalidSettings(): array
    {
        return [
            ['No parameters' => []],
            ['No server URL' => ['server_url' => '', 'api_key' => 'something', 'index_name' => 'fts_tuleap']],
            ['No api key' => ['server_url' => 'https://example.com', 'api_key' => '', 'index_name' => 'fts_tuleap']],
            ['No index name' => ['server_url' => 'https://example.com', 'api_key' => 'something', 'index_name' => '']],
            ['Server URL without HTTPS' => ['server_url' => 'http://example.com', 'api_key' => 'something', 'index_name' => 'fts_tuleap']],
        ];
    }

    private function buildController(ConfigDao $config_dao, bool $is_local_server): MeilisearchSaveAdminSettingsController
    {
        $key = $is_local_server ? new ConcealedString("a") : null;

        $csrf_token = $this->createStub(\CSRFSynchronizerToken::class);
        $csrf_token->method('check');

        $event_dispatcher = EventDispatcherStub::withCallback(
            function (GetConfigKeys $event): GetConfigKeys {
                $event->addConfigClass(RemoteMeilisearchServerSettings::class);
                return $event;
            }
        );

        $feedback_serializer = $this->createStub(FeedbackSerializer::class);
        $feedback_serializer->method('serialize');

        $root = vfsStream::setup()->url();
        \ForgeConfig::set('sys_custom_dir', $root);
        mkdir($root . '/conf/');

        return new MeilisearchSaveAdminSettingsController(
            new class ($key) implements IProvideCurrentKeyForLocalServer {
                public function __construct(private ?ConcealedString $key)
                {
                }

                public function getCurrentKey(): ?ConcealedString
                {
                    return $this->key;
                }
            },
            $csrf_token,
            new ConfigSet($event_dispatcher, $config_dao),
            MeilisearchServerURLValidator::buildSelf(),
            MeilisearchAPIKeyValidator::buildSelf(),
            MeilisearchIndexNameValidator::buildSelf(),
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            new SapiEmitter()
        );
    }
}
