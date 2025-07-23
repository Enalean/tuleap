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

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OnlyOfficeDocumentProviderTest extends TestCase
{
    private const ITEM_ID = 123;
    private \Project $project;

    #[\Override]
    protected function setUp(): void
    {
        $this->project = ProjectTestBuilder::aProject()->build();
    }

    public function testRejectsWhenLastVersionIsRejected(): void
    {
        $provider = $this->getOnlyOfficeDocumentProvider(
            ProvideDocmanFileLastVersionStub::buildWithError(),
            TransformDocmanFileLastVersionToOnlyOfficeDocumentStub::buildWithError(),
        );

        $result = $provider->getDocument(UserTestBuilder::buildWithDefaults(), self::ITEM_ID);

        self::assertTrue(Result::isErr($result));
    }

    public function testRejectsWhenFileCannotBeTransformedInOnlyOfficeDocument(): void
    {
        $provider = $this->getOnlyOfficeDocumentProvider(
            ProvideDocmanFileLastVersionStub::buildWithDocmanVersion(
                new \Docman_File(['item_id' => self::ITEM_ID]),
                new \Docman_Version(['filename' => 'not_something_onlyoffice_can_open'])
            ),
            TransformDocmanFileLastVersionToOnlyOfficeDocumentStub::buildWithError(),
        );

        $result = $provider->getDocument(UserTestBuilder::buildWithDefaults(), self::ITEM_ID);

        self::assertTrue(Result::isErr($result));
    }

    public function testHappyPath(): void
    {
        $item = new \Docman_File(['item_id' => self::ITEM_ID]);

        $provider = $this->getOnlyOfficeDocumentProvider(
            ProvideDocmanFileLastVersionStub::buildWithDocmanVersion(
                $item,
                new \Docman_Version(['filename' => 'spec.docx'])
            ),
            TransformDocmanFileLastVersionToOnlyOfficeDocumentStub::buildWithDocmanItem(
                $this->project,
                $item,
                123,
                'spec.docx',
            ),
        );

        $result = $provider->getDocument(UserTestBuilder::buildWithDefaults(), self::ITEM_ID);

        self::assertTrue(Result::isOk($result));
        self::assertSame($item, $result->unwrapOr(null)->item);
        self::assertSame($this->project, $result->unwrapOr(null)->project);
    }

    private function getOnlyOfficeDocumentProvider(
        ProvideDocmanFileLastVersion $last_version_provider,
        TransformDocmanFileLastVersionToOnlyOfficeDocument $transformer,
    ): OnlyOfficeDocumentProvider {
        return new OnlyOfficeDocumentProvider($last_version_provider, $transformer);
    }
}
