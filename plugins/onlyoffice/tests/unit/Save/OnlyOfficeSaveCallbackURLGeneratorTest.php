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

namespace Tuleap\OnlyOffice\Save;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\ForgeConfigSandbox;
use Tuleap\OnlyOffice\DocumentServer\DocumentServer;
use Tuleap\OnlyOffice\Open\Editor\OnlyOfficeDocumentConfig;
use Tuleap\OnlyOffice\Open\OnlyOfficeDocument;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\UUIDTestContext;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OnlyOfficeSaveCallbackURLGeneratorTest extends TestCase
{
    use ForgeConfigSandbox;

    protected function setUp(): void
    {
        \ForgeConfig::set('sys_default_domain', 'example.com');
    }

    public function testGetCallbackURLWhenASaveTokenCanBeGenerated(): void
    {
        $generated_url = self::generateCallbackURL(
            new class implements OnlyOfficeSaveDocumentTokenGenerator {
                public function generateSaveToken(
                    \PFUser $user,
                    OnlyOfficeDocument $document,
                    \DateTimeImmutable $now,
                ): ?ConcealedString {
                    return new ConcealedString('save_token');
                }
            }
        );

        self::assertEquals('https://example.com/onlyoffice/document_save?token=save_token', $generated_url);
    }

    public function testGetCallbackURLWhenASaveTokenCannotBeGenerated(): void
    {
        $generated_url = self::generateCallbackURL(
            new class implements OnlyOfficeSaveDocumentTokenGenerator {
                public function generateSaveToken(
                    \PFUser $user,
                    OnlyOfficeDocument $document,
                    \DateTimeImmutable $now,
                ): ?ConcealedString {
                    return null;
                }
            }
        );

        self::assertEquals('https://example.com/onlyoffice/document_save', $generated_url);
    }

    private static function generateCallbackURL(OnlyOfficeSaveDocumentTokenGenerator $save_document_token_generator): string
    {
        $url_generator = new OnlyOfficeSaveCallbackURLGenerator(
            $save_document_token_generator
        );

        return $url_generator->getCallbackURL(
            UserTestBuilder::buildWithDefaults(),
            OnlyOfficeDocumentConfig::fromDocument(
                new OnlyOfficeDocument(
                    ProjectTestBuilder::aProject()->build(),
                    new \Docman_Item(['item_id' => 741]),
                    852,
                    'doc.docx',
                    true,
                    DocumentServer::withoutProjectRestrictions(new UUIDTestContext(), 'https://example.com', new ConcealedString('very_secret')),
                ),
                new ConcealedString('download_token')
            ),
            new \DateTimeImmutable('@10'),
        );
    }
}
