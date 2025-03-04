<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

namespace Tuleap\Docman\Metadata\Owner;

use Project;
use Project_AccessRestrictedException;
use Tuleap\Document\Tree\IExtractProjectFromVariables;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Project\CheckProjectAccess;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Helpers\NoopSapiEmitter;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\BuildDisplayNameStub;
use Tuleap\Test\Stubs\ProvideCurrentUserStub;
use Tuleap\Test\Stubs\User\Avatar\ProvideUserAvatarUrlStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OwnerRequestHandlerTest extends TestCase
{
    /**
     * @throws \Tuleap\Request\NotFoundException
     * @throws \JsonException
     */
    public function testItReturns403IfTheUserCannotAccessToTheProject(): void
    {
        $response_factory = HTTPFactoryBuilder::responseFactory();
        $stream_factory   = HTTPFactoryBuilder::streamFactory();

        $owner_sender = new OwnerRequestHandler(
            new class implements RetrieveAllOwner {
                public function retrieveProjectDocumentOwnersForAutocomplete(Project $project, string $name_to_search): array
                {
                    return [];
                }
            },
            new class implements CheckProjectAccess {
                public function checkUserCanAccessProject(\PFUser $user, \Project $project): void
                {
                    throw new Project_AccessRestrictedException();
                }
            },
            ProvideCurrentUserStub::buildCurrentUserByDefault(),
            new class implements IExtractProjectFromVariables {
                public function getProject(array $variables): \Project
                {
                    return ProjectTestBuilder::aProject()->build();
                }
            },
            $response_factory,
            $stream_factory,
            new JSONResponseBuilder($response_factory, $stream_factory),
            new NoopSapiEmitter(),
        );

        $response = $owner_sender->handle(new NullServerRequest());

        self::assertEquals(403, $response->getStatusCode());
        self::assertEquals('Forbidden: Your not allowed to access this resource', $response->getBody()->getContents());
    }

    /**
     * @throws \Tuleap\Request\NotFoundException
     * @throws \JsonException
     */
    public function testItReturns400WhenTheQueryParamNameIsMissing(): void
    {
        $response_factory = HTTPFactoryBuilder::responseFactory();
        $stream_factory   = HTTPFactoryBuilder::streamFactory();

        $owner_sender = new OwnerRequestHandler(
            new class implements RetrieveAllOwner {
                public function retrieveProjectDocumentOwnersForAutocomplete(Project $project, string $name_to_search): array
                {
                    return [];
                }
            },
            new class implements CheckProjectAccess {
                public function checkUserCanAccessProject(\PFUser $user, \Project $project): void
                {
                    //do nothing
                }
            },
            ProvideCurrentUserStub::buildCurrentUserByDefault(),
            new class implements IExtractProjectFromVariables {
                public function getProject(array $variables): \Project
                {
                    return ProjectTestBuilder::aProject()->build();
                }
            },
            $response_factory,
            $stream_factory,
            new JSONResponseBuilder($response_factory, $stream_factory),
            new NoopSapiEmitter(),
        );

        $request  = (new NullServerRequest())->withQueryParams(['town' => 'test']);
        $response = $owner_sender->handle($request);

        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals("Bad request: The query parameter 'name' is missing or empty", $response->getBody()->getContents());
    }

    public function testItReturns400WhenTheQueryParamNameIsEmpty(): void
    {
        $response_factory = HTTPFactoryBuilder::responseFactory();
        $stream_factory   = HTTPFactoryBuilder::streamFactory();

        $owner_sender = new OwnerRequestHandler(
            new class implements RetrieveAllOwner {
                public function retrieveProjectDocumentOwnersForAutocomplete(Project $project, string $name_to_search): array
                {
                    return [];
                }
            },
            new class implements CheckProjectAccess {
                public function checkUserCanAccessProject(\PFUser $user, \Project $project): void
                {
                    //do nothing
                }
            },
            ProvideCurrentUserStub::buildCurrentUserByDefault(),
            new class implements IExtractProjectFromVariables {
                public function getProject(array $variables): \Project
                {
                    return ProjectTestBuilder::aProject()->build();
                }
            },
            $response_factory,
            $stream_factory,
            new JSONResponseBuilder($response_factory, $stream_factory),
            new NoopSapiEmitter(),
        );

        $request  = (new NullServerRequest())->withQueryParams(['name' => '']);
        $response = $owner_sender->handle($request);

        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals("Bad request: The query parameter 'name' is missing or empty", $response->getBody()->getContents());
    }

    /**
     * @throws \Tuleap\Request\NotFoundException
     * @throws \JsonException
     */
    public function testItReturnsAllDocumentOwnersOfTheProject(): void
    {
        $response_factory = HTTPFactoryBuilder::responseFactory();
        $stream_factory   = HTTPFactoryBuilder::streamFactory();

        $owner_sender = new OwnerRequestHandler(
            new class implements RetrieveAllOwner {
                public function retrieveProjectDocumentOwnersForAutocomplete(Project $project, string $name_to_search): array
                {
                    return [
                        OwnerRepresentationForAutocomplete::buildForSelect2AutocompleteFromOwner(
                            UserTestBuilder::aUser()
                                ->withId(101)
                                ->withUserName('knopel')
                                ->withRealName('Leslie Knope')
                                ->build(),
                            BuildDisplayNameStub::build(),
                            ProvideUserAvatarUrlStub::build(),
                        ),
                        OwnerRepresentationForAutocomplete::buildForSelect2AutocompleteFromOwner(
                            UserTestBuilder::aUser()
                                ->withId(102)
                                ->withUserName('swansonr')
                                ->withRealName('Ron Swanson')
                                ->build(),
                            BuildDisplayNameStub::build(),
                            ProvideUserAvatarUrlStub::build(),
                        ),
                    ];
                }
            },
            new class implements CheckProjectAccess {
                public function checkUserCanAccessProject(\PFUser $user, \Project $project): void
                {
                    //do nothing
                }
            },
            ProvideCurrentUserStub::buildCurrentUserByDefault(),
            new class implements IExtractProjectFromVariables {
                public function getProject(array $variables): \Project
                {
                    return ProjectTestBuilder::aProject()->build();
                }
            },
            $response_factory,
            $stream_factory,
            new JSONResponseBuilder($response_factory, $stream_factory),
            new NoopSapiEmitter(),
        );

        $request  = (new NullServerRequest())->withQueryParams(['name' => 'o']);
        $response = $owner_sender->handle($request);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals(
            '{"results":' .
            '[{"text":"Leslie Knope (knopel)","tuleap_user_id":101,"username":"knopel","avatar_url":"avatar.png","has_avatar":true},' .
            '{"text":"Ron Swanson (swansonr)","tuleap_user_id":102,"username":"swansonr","avatar_url":"avatar.png","has_avatar":true}' .
            ']}',
            $response->getBody()->getContents()
        );
    }
}
