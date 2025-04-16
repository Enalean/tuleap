<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Permissions\Admin;

use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Layout\Feedback\FeedbackSerializer;
use Tuleap\MediawikiStandalone\Permissions\ISaveProjectPermissionsStub;
use Tuleap\MediawikiStandalone\Service\MediawikiStandaloneService;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Helpers\NoopSapiEmitter;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CSRFSynchronizerTokenStub;
use Tuleap\Test\Stubs\EnqueueTaskStub;
use Tuleap\Test\Stubs\UGroupRetrieverStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class AdminSavePermissionsControllerTest extends TestCase
{
    private const PROJECT_ID = 101;

    public function testSavePermissions(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withId(self::PROJECT_ID)
            ->withUsedService(MediawikiStandaloneService::SERVICE_SHORTNAME)
            ->build();

        $feedback_serializer = $this->createStub(FeedbackSerializer::class);
        $feedback_serializer->method('serialize');

        $token          = CSRFSynchronizerTokenStub::buildSelf();
        $token_provider = $this->createMock(CSRFSynchronizerTokenProvider::class);
        $token_provider->method('getCSRF')->willReturn($token);

        $developers = ProjectUGroupTestBuilder::aCustomUserGroup(102)->withName('Developers')->build();
        $qa         = ProjectUGroupTestBuilder::aCustomUserGroup(103)->withName('QA')->build();

        $history_dao     = $this->createMock(\ProjectHistoryDao::class);
        $permissions_dao = ISaveProjectPermissionsStub::buildSelf();

        $controller = new AdminSavePermissionsController(
            new ProjectPermissionsSaver(
                $permissions_dao,
                $history_dao,
                new EnqueueTaskStub(),
            ),
            new UserGroupToSaveRetriever(
                UGroupRetrieverStub::buildWithUserGroups(
                    ProjectUGroupTestBuilder::buildProjectMembers(),
                    $developers,
                    $qa,
                ),
            ),
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            $token_provider,
            new NoopSapiEmitter(),
        );

        $request = (new NullServerRequest())
            ->withAttribute(\Project::class, $project)
            ->withAttribute(\PFUser::class, UserTestBuilder::anActiveUser()->build())
            ->withParsedBody(
                [
                    'readers' => ['102', '103'],
                    'writers' => ['103'],
                    'admins'  => ['102'],
                ]
            );

        $history_dao
            ->expects($this->exactly(3))
            ->method('groupAddHistory')
            ->willReturnCallback(
                function (string $field_name, string $value, int $project_id): void {
                    match (true) {
                        $field_name === 'perm_granted_for_mediawiki_standalone_readers' && $value === 'Developers,QA' && $project_id === self::PROJECT_ID,
                           $field_name === 'perm_granted_for_mediawiki_standalone_writers' && $value === 'QA' && $project_id === self::PROJECT_ID,
                           $field_name === 'perm_granted_for_mediawiki_standalone_admins' && $value === 'Developers' && $project_id === self::PROJECT_ID => true,
                        default => throw new \LogicException(sprintf('Not expected call to groupAddHistory (%s | %s | %d)', $field_name, $value, $project_id))
                    };
                }
            );

        $response = $controller->handle($request);

        self::assertTrue($token->hasBeenChecked());
        self::assertEquals(302, $response->getStatusCode());

        self::assertEquals(
            [102, 103],
            $permissions_dao->getCapturedReadersUgroupIds()
        );
        self::assertEquals(
            [103],
            $permissions_dao->getCapturedWritersUgroupIds()
        );
    }
}
