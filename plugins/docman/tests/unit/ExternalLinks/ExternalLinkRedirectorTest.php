<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\ExternalLinks;

use HTTPRequest;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ExternalLinkRedirectorTest extends TestCase
{
    private HTTPRequest&MockObject $request;

    #[\Override]
    protected function setUp(): void
    {
        $this->request = $this->createMock(HTTPRequest::class);
        $this->request->method('getProject')->willReturn(ProjectTestBuilder::aProject()->withUnixName('project-short-name')->build());
    }

    public function testItShouldDoNothingIfUserIsAnonymous(): void
    {
        $folder_id      = 10;
        $root_folder_id = 3;
        $redirector     = new ExternalLinkRedirector(UserTestBuilder::anAnonymousUser()->build(), $this->request, $folder_id, $root_folder_id);

        $this->request->method('exist')->with('action')->willReturn(false);

        $redirector->checkAndStoreIfUserHasToBeenRedirected();
        self::assertFalse($redirector->shouldRedirectUserOnNewUI());
    }

    public function testItShouldNotRedirectWhenRequestIsForDocmanAdministrationUI(): void
    {
        $folder_id      = 10;
        $root_folder_id = 3;
        $redirector     = new ExternalLinkRedirector(UserTestBuilder::buildWithDefaults(), $this->request, $folder_id, $root_folder_id);

        $this->request->method('exist')->with('action')->willReturn(true);

        $redirector->checkAndStoreIfUserHasToBeenRedirected();
        self::assertFalse($redirector->shouldRedirectUserOnNewUI());
    }

    public function testItShouldNotAutomaticallyRedirectUser(): void
    {
        $folder_id      = 10;
        $root_folder_id = 3;
        $redirector     = new ExternalLinkRedirector(UserTestBuilder::buildWithDefaults(), $this->request, $folder_id, $root_folder_id);
        $matcher        = $this->exactly(2);

        $this->request->expects($matcher)->method('exist')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame('action', $parameters[0]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame('group_id', $parameters[0]);
            }
            return false;
        });

        $redirector->checkAndStoreIfUserHasToBeenRedirected();
        self::assertFalse($redirector->shouldRedirectUserOnNewUI());
    }

    public function testItShouldRedirectWhenUrlIsForAccessingToASpecificDocument(): void
    {
        $folder_id      = 10;
        $root_folder_id = 3;
        $redirector     = new ExternalLinkRedirector(UserTestBuilder::buildWithDefaults(), $this->request, $folder_id, $root_folder_id);

        $this->request->method('exist')->willReturnCallback(static fn(string $variable) => match ($variable) {
            'action'   => false,
            'group_id' => 102,
            'id'       => $folder_id,
        });
        $this->request->method('get')->with('id')->willReturn($folder_id);

        $redirector->checkAndStoreIfUserHasToBeenRedirected();

        self::assertTrue($redirector->shouldRedirectUserOnNewUI());
        self::assertEquals('/plugins/document/project-short-name/preview/10', $redirector->getUrlRedirection());
    }

    public function testItShouldStoreDocumentIdAndRedirectToRootWhenUrlIsForAccessingRootDocument(): void
    {
        $folder_id      = 0;
        $root_folder_id = 3;
        $redirector     = new ExternalLinkRedirector(UserTestBuilder::buildWithDefaults(), $this->request, $folder_id, $root_folder_id);

        $this->request->method('exist')->willReturnCallback(static fn(string $variable) => match ($variable) {
            'action'   => false,
            'group_id' => 102,
            'id'       => $root_folder_id,
        });
        $this->request->method('get')->with('id')->willReturn($root_folder_id);

        $redirector->checkAndStoreIfUserHasToBeenRedirected();

        self::assertTrue($redirector->shouldRedirectUserOnNewUI());
        self::assertEquals('/plugins/document/project-short-name/', $redirector->getUrlRedirection());
    }
}
