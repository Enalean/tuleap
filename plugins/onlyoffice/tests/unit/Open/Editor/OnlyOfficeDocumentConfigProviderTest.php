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

namespace Tuleap\OnlyOffice\Open\Editor;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\ForgeConfigSandbox;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\OnlyOffice\Download\OnlyOfficeDownloadDocumentTokenGenerator;
use Tuleap\OnlyOffice\Open\OnlyOfficeDocument;
use Tuleap\OnlyOffice\Open\ProvideOnlyOfficeDocument;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class OnlyOfficeDocumentConfigProviderTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testProvidesDocumentConfig(): void
    {
        $provider = self::buildProvider(
            Result::ok(
                new OnlyOfficeDocument(
                    ProjectTestBuilder::aProject()->build(),
                    new \Docman_Item(['item_id' => 123]),
                    963,
                    'something.docx'
                )
            )
        );

        $result = $provider->getDocumentConfig(UserTestBuilder::buildWithDefaults(), 123, new \DateTimeImmutable('@10'));

        \ForgeConfig::set('sys_default_domain', 'example.com');

        self::assertEquals(
            new OnlyOfficeDocumentConfig('docx', 'tuleap_document_123_963', 'something.docx', 'https:///onlyoffice/document_download?token=AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'),
            $result->unwrapOr(null)
        );
    }

    public function testRejectsWhenLastVersionOfADocumentCannotBeFound(): void
    {
        $provider = self::buildProvider(
            Result::err(Fault::fromMessage('Cannot retrieve doc'))
        );

        $result = $provider->getDocumentConfig(UserTestBuilder::buildWithDefaults(), 404, new \DateTimeImmutable('@10'));

        self::assertTrue(Result::isErr($result));
    }

    /**
     * @param Ok<OnlyOfficeDocument>|Err<Fault> $result_document
     */
    private static function buildProvider(Ok|Err $result_document): OnlyOfficeDocumentConfigProvider
    {
        return new OnlyOfficeDocumentConfigProvider(
            new class ($result_document) implements ProvideOnlyOfficeDocument {
                public function __construct(private Ok|Err $result)
                {
                }

                public function getDocument(\PFUser $user, int $item_id): Ok|Err
                {
                    return $this->result;
                }
            },
            new class implements OnlyOfficeDownloadDocumentTokenGenerator {
                public function generateDownloadToken(
                    \PFUser $user,
                    OnlyOfficeDocument $document,
                    \DateTimeImmutable $now,
                ): ConcealedString {
                    return new ConcealedString(str_repeat('A', 32));
                }
            }
        );
    }
}
