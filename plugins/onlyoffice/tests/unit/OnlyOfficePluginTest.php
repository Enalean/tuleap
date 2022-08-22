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

namespace Tuleap\OnlyOffice;

use onlyofficePlugin;
use Tuleap\OnlyOffice\Administration\OnlyOfficeDocumentServerSettings;
use Tuleap\Test\PHPUnit\TestCase;

final class OnlyOfficePluginTest extends TestCase
{
    use \Tuleap\ForgeConfigSandbox;

    public function testOpenItemHrefOverridesIfFileIsSupported(): void
    {
        \ForgeConfig::set(OnlyOfficeDocumentServerSettings::URL, 'https://example.com');
        \ForgeConfig::set(OnlyOfficeDocumentServerSettings::SECRET, 'secret');

        $open_item_href = new \Tuleap\Docman\REST\v1\OpenItemHref(
            new \Docman_File(['item_id'     => '123']),
            new \Docman_Version(['filename' => 'test.docx'])
        );

        (new onlyofficePlugin(null))->openItemHref($open_item_href);

        self::assertEquals('/onlyoffice/open/123', $open_item_href->getHref());
    }

    public function testOpenItemHrefDoesNotOverrideIfFileIsNotSupported(): void
    {
        \ForgeConfig::set(OnlyOfficeDocumentServerSettings::URL, 'https://example.com');
        \ForgeConfig::set(OnlyOfficeDocumentServerSettings::SECRET, 'secret');

        $open_item_href = new \Tuleap\Docman\REST\v1\OpenItemHref(
            new \Docman_File(['item_id'     => '123']),
            new \Docman_Version(['filename' => 'test.png'])
        );

        (new onlyofficePlugin(null))->openItemHref($open_item_href);

        self::assertNull($open_item_href->getHref());
    }

    public function testOpenItemHrefDoesNotOverrideIfOnlyOfficeUrlIsNotSet(): void
    {
        \ForgeConfig::set(OnlyOfficeDocumentServerSettings::SECRET, 'secret');

        $open_item_href = new \Tuleap\Docman\REST\v1\OpenItemHref(
            new \Docman_File(['item_id'     => '123']),
            new \Docman_Version(['filename' => 'test.docx'])
        );

        (new onlyofficePlugin(null))->openItemHref($open_item_href);

        self::assertNull($open_item_href->getHref());
    }

    public function testOpenItemHrefDoesNotOverrideIfOnlyOfficeSecretIsNotSet(): void
    {
        \ForgeConfig::set(OnlyOfficeDocumentServerSettings::URL, 'https://example.com');

        $open_item_href = new \Tuleap\Docman\REST\v1\OpenItemHref(
            new \Docman_File(['item_id'     => '123']),
            new \Docman_Version(['filename' => 'test.docx'])
        );

        (new onlyofficePlugin(null))->openItemHref($open_item_href);

        self::assertNull($open_item_href->getHref());
    }
}
