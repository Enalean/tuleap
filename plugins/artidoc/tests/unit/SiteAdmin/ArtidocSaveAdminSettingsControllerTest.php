<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\SiteAdmin;

use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use PHPUnit\Framework\Attributes\TestWith;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Layout\Feedback\FeedbackSerializer;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\Config\ConfigUpdaterStub;
use Tuleap\Test\Stubs\CSRFSynchronizerTokenStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtidocSaveAdminSettingsControllerTest extends TestCase
{
    use ForgeConfigSandbox;

    #[TestWith(['', '0'])]
    #[TestWith(['0', '0'])]
    #[TestWith(['1', '1'])]
    #[TestWith(['whatever', '0'])]
    public function testSaveSettings(string $submitted, string $expected): void
    {
        $config_updater = ConfigUpdaterStub::build();

        $csrf_token = CSRFSynchronizerTokenStub::buildSelf();

        $feedback_serializer = $this->createStub(FeedbackSerializer::class);
        $feedback_serializer->method('serialize');

        $controller = new ArtidocSaveAdminSettingsController(
            $csrf_token,
            $config_updater,
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            new SapiEmitter()
        );

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, UserTestBuilder::anActiveUser()->build())
            ->withParsedBody(
                ['can_user_display_versions' => $submitted]
            );

        $response = $controller->handle($request);
        self::assertSame(302, $response->getStatusCode());
        self::assertSame(
            $expected,
            $config_updater->getUpdatedConfig(\ForgeConfig::FEATURE_FLAG_PREFIX . ArtidocAdminSettings::FEATURE_FLAG_VERSIONS),
        );

        self::assertTrue($csrf_token->hasBeenChecked());
    }
}
