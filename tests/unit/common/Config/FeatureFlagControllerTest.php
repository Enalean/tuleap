<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Config;

use ForgeConfig;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Test\Helpers\NoopSapiEmitter;
use Tuleap\Test\PHPUnit\TestCase;

final class FeatureFlagControllerTest extends TestCase
{
    use ForgeConfigSandbox;

    private const FEATURE_FLAG_KEY = 'stop_linking_artifact';

    protected function setUp(): void
    {
        ForgeConfig::set(ForgeConfig::FEATURE_FLAG_PREFIX . self::FEATURE_FLAG_KEY, 1);
    }

    private function handle(ServerRequestInterface $request): ResponseInterface
    {
        $controller = new FeatureFlagController(
            new JSONResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory()),
            new NoopSapiEmitter(),
        );
        return $controller->handle($request);
    }

    private function expectedJSONError(string $reason): string
    {
        return json_encode(['error' => ['message' => $reason]], JSON_THROW_ON_ERROR);
    }

    public static function provideInvalidNames(): iterable
    {
        yield 'Name param is missing' => [['some_param' => 'feature_flag_not_feature_flag'], 'Bad request: the query parameter "name" is missing'];
        yield 'Name param is empty' => [['name' => ''], 'Bad request: the name given is not a feature flag'];
        yield 'Name does not have feature flag prefix' => [['name' => 'hehehe'], 'Bad request: the name given is not a feature flag'];
    }

    /**
     * @dataProvider provideInvalidNames
     */
    public function testItReturnsBadRequestWhenInvalidParameter(array $query_parameters, string $expected_error): void
    {
        $request = (new NullServerRequest())->withQueryParams($query_parameters);

        $response = $this->handle($request);

        self::assertSame(400, $response->getStatusCode());
        self::assertSame($this->expectedJSONError($expected_error), $response->getBody()->getContents());
    }

    public function testItReturnsBadRequestIfTheGivenFeatureFlagIsNotSet(): void
    {
        ForgeConfig::set(ForgeConfig::FEATURE_FLAG_PREFIX . 'feature_flag_not_feature_flag', null);

        $request = (new NullServerRequest())->withQueryParams(['name' => 'feature_flag_not_feature_flag']);

        $response = $this->handle($request);

        self::assertSame(400, $response->getStatusCode());
        self::assertSame(
            $this->expectedJSONError('Bad request: the feature flag is not set'),
            $response->getBody()->getContents()
        );
    }

    public function testItReturnsTheFeatureFlagValue(): void
    {
        $request = (new NullServerRequest())->withQueryParams(
            ['name' => ForgeConfig::FEATURE_FLAG_PREFIX . self::FEATURE_FLAG_KEY]
        );

        $response = $this->handle($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(json_encode(['value' => 1]), $response->getBody()->getContents());
    }
}
