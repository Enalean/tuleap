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

namespace Tuleap\OnlyOffice\Open;

use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProjectByIDFactoryStub;

class OnlyOfficeDocumentProviderTest extends TestCase
{
    private const PROJECT_ID = 101;
    private const ITEM_ID    = 123;

    private \Project $project;

    protected function setUp(): void
    {
        $this->project = ProjectTestBuilder::aProject()
            ->withId(self::PROJECT_ID)
            ->build();
    }

    public function testRejectsWhenLastVersionIsRejected(): void
    {
        $provider = $this->getOnlyOfficeDocumentProvider(ProvideDocmanFileLastVersionStub::buildWithError());

        $result = $provider->getDocument(UserTestBuilder::buildWithDefaults(), self::ITEM_ID);

        self::assertTrue(Result::isErr($result));
    }

    public function testRejectsWhenFileCannotBeOpenInOnlyOffice(): void
    {
        $provider = $this->getOnlyOfficeDocumentProvider(
            ProvideDocmanFileLastVersionStub::buildWithDocmanVersion(
                new \Docman_File(['group_id' => self::PROJECT_ID]),
                new \Docman_Version(['filename' => 'not_something_onlyoffice_can_open'])
            )
        );

        $result = $provider->getDocument(UserTestBuilder::buildWithDefaults(), self::ITEM_ID);

        self::assertTrue(Result::isErr($result));
    }

    public function testHappyPath(): void
    {
        $item = new \Docman_File(['group_id' => self::PROJECT_ID]);

        $provider = $this->getOnlyOfficeDocumentProvider(
            ProvideDocmanFileLastVersionStub::buildWithDocmanVersion(
                $item,
                new \Docman_Version(['filename' => 'spec.docx'])
            )
        );

        $result = $provider->getDocument(UserTestBuilder::buildWithDefaults(), self::ITEM_ID);

        self::assertTrue(Result::isOk($result));
        self::assertSame($item, $result->unwrapOr(null)->item);
        self::assertSame($this->project, $result->unwrapOr(null)->project);
    }

    private function getOnlyOfficeDocumentProvider(
        ProvideDocmanFileLastVersion $last_version_provider,
    ): OnlyOfficeDocumentProvider {
        return new OnlyOfficeDocumentProvider(
            $last_version_provider,
            ProjectByIDFactoryStub::buildWith($this->project)
        );
    }
}
