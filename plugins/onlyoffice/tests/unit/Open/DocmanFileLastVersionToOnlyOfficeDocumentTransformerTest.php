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

use Tuleap\Cryptography\ConcealedString;
use Tuleap\NeverThrow\Result;
use Tuleap\OnlyOffice\Administration\CheckOnlyOfficeIsAvailable;
use Tuleap\OnlyOffice\DocumentServer\DocumentServer;
use Tuleap\OnlyOffice\Stubs\IRetrieveDocumentServersStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\DB\UUIDTestContext;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProjectByIDFactoryStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class DocmanFileLastVersionToOnlyOfficeDocumentTransformerTest extends TestCase
{
    private const PROJECT_ID = 101;
    private const ITEM_ID    = 123;

    private const ONLYOFFICE_IS_AVAILABLE     = true;
    private const ONLYOFFICE_IS_NOT_AVAILABLE = false;

    private \Project $project;

    #[\Override]
    protected function setUp(): void
    {
        $this->project = ProjectTestBuilder::aProject()
            ->withId(self::PROJECT_ID)
            ->build();
    }

    public function testRejectsWhenOnlyOfficeIsNotAvailableForProject(): void
    {
        $provider = $this->getTransformer(self::ONLYOFFICE_IS_NOT_AVAILABLE);

        $result = $provider->transformToOnlyOfficeDocument(
            new DocmanFileLastVersion(
                new \Docman_File(['group_id' => self::PROJECT_ID]),
                new \Docman_Version(['filename' => 'spec.docx']),
                true,
            ),
        );

        self::assertTrue(Result::isErr($result));
    }

    public function testRejectsWhenFileCannotBeOpenInOnlyOffice(): void
    {
        $provider = $this->getTransformer(self::ONLYOFFICE_IS_AVAILABLE);

        $result = $provider->transformToOnlyOfficeDocument(
            new DocmanFileLastVersion(
                new \Docman_File(['group_id' => self::PROJECT_ID]),
                new \Docman_Version(['filename' => 'not_something_onlyoffice_can_open']),
                false,
            ),
        );

        self::assertTrue(Result::isErr($result));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderHappyPath')]
    public function testHappyPath(string $filename, bool $user_can_write_it, bool $expected_can_be_edited): void
    {
        $provider = $this->getTransformer(self::ONLYOFFICE_IS_AVAILABLE);

        $item   = new \Docman_File(['group_id' => self::PROJECT_ID]);
        $result = $provider->transformToOnlyOfficeDocument(
            new DocmanFileLastVersion(
                $item,
                new \Docman_Version(['filename' => $filename]),
                $user_can_write_it,
            ),
        );

        self::assertTrue(Result::isOk($result));
        self::assertSame($item, $result->unwrapOr(null)->item);
        self::assertSame($this->project, $result->unwrapOr(null)->project);
        self::assertSame($expected_can_be_edited, $result->unwrapOr(null)->can_be_edited);
    }

    public static function dataProviderHappyPath(): array
    {
        return [
            'Editable document user can write' => ['spec.docx', true, true],
            'Editable document user cannot write' => ['spec.docx', false, false],
            'Non editable document user can write' => ['spec.csv', true, false],
            'Non editable document user cannot write' => ['spec.csv', false, false],
        ];
    }

    private function getTransformer(
        bool $is_onlyoffice_available_for_project,
    ): DocmanFileLastVersionToOnlyOfficeDocumentTransformer {
        return new DocmanFileLastVersionToOnlyOfficeDocumentTransformer(
            new class ($is_onlyoffice_available_for_project) implements CheckOnlyOfficeIsAvailable {
                public function __construct(private bool $is_onlyoffice_available_for_project)
                {
                }

                #[\Override]
                public function isOnlyOfficeIntegrationAvailableForProject(\Project $project): bool
                {
                    return $this->is_onlyoffice_available_for_project;
                }
            },
            ProjectByIDFactoryStub::buildWith($this->project),
            IRetrieveDocumentServersStub::buildWith(DocumentServer::withoutProjectRestrictions(new UUIDTestContext(), 'https://example.com', new ConcealedString('very_secret'))),
        );
    }
}
