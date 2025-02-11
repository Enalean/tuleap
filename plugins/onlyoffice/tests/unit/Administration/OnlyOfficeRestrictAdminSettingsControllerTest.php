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
use Tuleap\Cryptography\ConcealedString;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Layout\Feedback\FeedbackSerializer;
use Tuleap\OnlyOffice\DocumentServer\DocumentServer;
use Tuleap\OnlyOffice\DocumentServer\IRestrictDocumentServer;
use Tuleap\OnlyOffice\DocumentServer\IRetrieveDocumentServers;
use Tuleap\OnlyOffice\DocumentServer\RestrictedProject;
use Tuleap\OnlyOffice\Stubs\IRestrictDocumentServerStub;
use Tuleap\OnlyOffice\Stubs\IRetrieveDocumentServersStub;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\UUIDTestContext;
use Tuleap\Test\PHPUnit\TestCase;

final class OnlyOfficeRestrictAdminSettingsControllerTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testSaveRestrictionAddsProject(): void
    {
        $server_id  = new UUIDTestContext();
        $restrictor = IRestrictDocumentServerStub::buildSelf();
        $retriever  = IRetrieveDocumentServersStub::buildWith(
            DocumentServer::withProjectRestrictions(
                $server_id,
                'https://example.com',
                new ConcealedString('secret'),
                [
                    101 => new RestrictedProject(101, 'acme', 'Acme Project'),
                ],
            ),
        );

        $controller = $this->buildController($retriever, $restrictor);

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, UserTestBuilder::anActiveUser()->build())
            ->withAttribute('id', $server_id->toString())
            ->withParsedBody(['is_restricted' => '1', 'project-to-add' => '102']);

        $response = $controller->handle($request);

        self::assertEquals(302, $response->getStatusCode());
        self::assertFalse($restrictor->hasBeenUnrestricted());
        self::assertTrue($restrictor->hasBeenRestrictedWith([101, 102]));
    }

    public function testSaveRestrictionRemovesProject(): void
    {
        $server_id  = new UUIDTestContext();
        $restrictor = IRestrictDocumentServerStub::buildSelf();
        $retriever  = IRetrieveDocumentServersStub::buildWith(
            DocumentServer::withProjectRestrictions(
                $server_id,
                'https://example.com',
                new ConcealedString('secret'),
                [
                    101 => new RestrictedProject(101, 'acme', 'Acme Project'),
                    102 => new RestrictedProject(101, 'evil', 'Evil Corp'),
                ],
            ),
        );

        $controller = $this->buildController($retriever, $restrictor);

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, UserTestBuilder::anActiveUser()->build())
            ->withAttribute('id', $server_id->toString())
            ->withParsedBody(['is_restricted' => '1', 'projects-to-remove' => ['102']]);

        $response = $controller->handle($request);

        self::assertEquals(302, $response->getStatusCode());
        self::assertFalse($restrictor->hasBeenUnrestricted());
        self::assertTrue($restrictor->hasBeenRestrictedWith([101]));
    }

    public function testSaveTheFirstRestriction(): void
    {
        $server_id  = new UUIDTestContext();
        $restrictor = IRestrictDocumentServerStub::buildSelf();
        $retriever  = IRetrieveDocumentServersStub::buildWith(
            DocumentServer::withoutProjectRestrictions($server_id, 'https://example.com', new ConcealedString('secret'))
        );

        $controller = $this->buildController($retriever, $restrictor);

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, UserTestBuilder::anActiveUser()->build())
            ->withAttribute('id', $server_id->toString())
            ->withParsedBody(['is_restricted' => '1']);

        $response = $controller->handle($request);

        self::assertEquals(302, $response->getStatusCode());
        self::assertFalse($restrictor->hasBeenUnrestricted());
        self::assertTrue($restrictor->hasBeenRestrictedWith([]));
    }

    public function testSaveUnrestriction(): void
    {
        $server_id  = new UUIDTestContext();
        $restrictor = IRestrictDocumentServerStub::buildSelf();
        $retriever  = IRetrieveDocumentServersStub::buildWith(
            DocumentServer::withProjectRestrictions(
                $server_id,
                'https://example.com',
                new ConcealedString('secret'),
                [
                    101 => new RestrictedProject(101, 'acme', 'Acme Project'),
                ],
            ),
        );

        $controller = $this->buildController($retriever, $restrictor);

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, UserTestBuilder::anActiveUser()->build())
            ->withAttribute('id', $server_id->toString())
            ->withParsedBody(['is_restricted' => '0']);

        $response = $controller->handle($request);

        self::assertEquals(302, $response->getStatusCode());
        self::assertFalse($restrictor->hasBeenRestricted());
        self::assertTrue($restrictor->hasBeenUnrestricted());
    }

    public function testSaveUnrestrictionFailsIfTooManyServers(): void
    {
        $server_id  = new UUIDTestContext();
        $restrictor = IRestrictDocumentServerStub::buildWithTooManyServersForUnrestriction();
        $retriever  = IRetrieveDocumentServersStub::buildWith(
            DocumentServer::withProjectRestrictions(
                $server_id,
                'https://example.com/a',
                new ConcealedString('secret'),
                [],
            ),
            DocumentServer::withProjectRestrictions(
                new UUIDTestContext(),
                'https://example.com/b',
                new ConcealedString('secret'),
                [],
            ),
        );

        $controller = $this->buildController($retriever, $restrictor);

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, UserTestBuilder::anActiveUser()->build())
            ->withAttribute('id', $server_id->toString())
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
        $server_id = new UUIDTestContext();
        $retriever = IRetrieveDocumentServersStub::buildWith(
            DocumentServer::withoutProjectRestrictions($server_id, 'https://example.com', new ConcealedString('secret'))
        );

        $controller = $this->buildController($retriever, IRestrictDocumentServerStub::buildSelf());

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, UserTestBuilder::anActiveUser()->build())
            ->withAttribute('id', $server_id->toString())
            ->withParsedBody($body);

        $this->expectException(ForbiddenException::class);
        $controller->handle($request);
    }

    public static function dataProviderInvalidSettings(): array
    {
        return [
            'No parameters' => [[]],
            'is_restricted is not in the body' => [
                [
                    'project-to-add' => '1',
                ],
            ],
            'Project to add is not a numeric' => [
                [
                    'is_restricted'  => '1',
                    'project-to-add' => 'not a numeric',
                ],
            ],
            'Projects to remove is not an array' => [
                [
                    'is_restricted'      => '1',
                    'projects-to-remove' => 'not an array',
                ],
            ],
        ];
    }

    private function buildController(
        IRetrieveDocumentServers $retriever,
        IRestrictDocumentServer $restrictor,
    ): OnlyOfficeRestrictAdminSettingsController {
        $csrf_token = $this->createStub(\CSRFSynchronizerToken::class);
        $csrf_token->method('check');

        $feedback_serializer = $this->createStub(FeedbackSerializer::class);
        $feedback_serializer->method('serialize');

        return new OnlyOfficeRestrictAdminSettingsController(
            $csrf_token,
            $retriever,
            $restrictor,
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            new SapiEmitter()
        );
    }
}
