<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\XML\Import;

use DateTimeImmutable;
use PFUser;
use SimpleXMLElement;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\User\XML\Import\IFindUserFromXMLReferenceStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ImportPropertiesExtractorTest extends TestCase
{
    private DateTimeImmutable $current_date;
    private ImportPropertiesExtractor $properties_extractor;
    private PFUser $current_user;

    protected function setUp(): void
    {
        $this->current_date = new DateTimeImmutable();
        $this->current_user = UserTestBuilder::buildWithDefaults();
        $user_finder        = IFindUserFromXMLReferenceStub::buildWithUser($this->current_user);

        $this->properties_extractor = new ImportPropertiesExtractor($this->current_date, $this->current_user, $user_finder);
    }

    public function testImportEmpty(): void
    {
        $node = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <item type="empty">
                <properties>
                    <title>My document</title>
                </properties>
            </item>
            EOS
        );

        $properties = $this->properties_extractor->getImportProperties($node);

        self::assertEquals(PLUGIN_DOCMAN_ITEM_TYPE_EMPTY, $properties->getItemTypeId());
        self::assertEquals('My document', $properties->getTitle());
        self::assertEquals('', $properties->getDescription());
        self::assertEquals($this->current_date, $properties->getCreateDate());
        self::assertEquals($this->current_date, $properties->getUpdateDate());
        self::assertEquals($this->current_user, $properties->getOwner());
        self::assertNull($properties->getLinkUrl());
    }

    public function testImportWithDescription(): void
    {
        $node = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <item type="empty">
                <properties>
                    <title>My document</title>
                    <description>Lorem ipsum</description>
                </properties>
            </item>
            EOS
        );

        $properties = $this->properties_extractor->getImportProperties($node);

        self::assertEquals('Lorem ipsum', $properties->getDescription());
    }

    public function testImportWithUpdateDate(): void
    {
        $node = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <item type="empty">
                <properties>
                    <title>My document</title>
                    <update_date format="ISO8601">2009-02-14T00:31:30+01:00</update_date>
                </properties>
            </item>
            EOS
        );

        $properties = $this->properties_extractor->getImportProperties($node);

        self::assertEquals(
            (new DateTimeImmutable())->setTimestamp(1234567890),
            $properties->getUpdateDate()
        );
    }

    public function testCreateDateEqualsToUpdateDateWhenNotSpecified(): void
    {
        $node = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <item type="empty">
                <properties>
                    <title>My document</title>
                    <update_date format="ISO8601">2009-02-14T00:31:30+01:00</update_date>
                </properties>
            </item>
            EOS
        );

        $properties = $this->properties_extractor->getImportProperties($node);

        $expected_update_date = (new DateTimeImmutable())->setTimestamp(1234567890);
        self::assertEquals($expected_update_date, $properties->getUpdateDate());
        self::assertEquals($expected_update_date, $properties->getCreateDate());
    }

    public function testImportWithCreateDate(): void
    {
        $node = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <item type="empty">
                <properties>
                    <title>My document</title>
                    <create_date format="ISO8601">2009-02-14T00:31:30+01:00</create_date>
                </properties>
            </item>
            EOS
        );

        $properties = $this->properties_extractor->getImportProperties($node);

        self::assertEquals(
            (new DateTimeImmutable())->setTimestamp(1234567890),
            $properties->getCreateDate()
        );
    }

    public function testImportWithCreateDateAndUpdateDate(): void
    {
        $node = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <item type="empty">
                <properties>
                    <title>My document</title>
                    <create_date format="ISO8601">2009-02-14T00:31:30+01:00</create_date>
                    <update_date format="ISO8601">2011-12-22T16:31:30+01:00</update_date>
                </properties>
            </item>
            EOS
        );

        $properties = $this->properties_extractor->getImportProperties($node);

        self::assertEquals(
            (new DateTimeImmutable())->setTimestamp(1234567890),
            $properties->getCreateDate()
        );
        self::assertEquals(
            (new DateTimeImmutable())->setTimestamp(1324567890),
            $properties->getUpdateDate()
        );
    }

    public function testImportOwner(): void
    {
        $node = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <item type="empty">
                <properties>
                    <title>My document</title>
                    <owner format="ldap">103</owner>
                </properties>
            </item>
            EOS
        );

        $properties = $this->properties_extractor->getImportProperties($node);

        self::assertEquals($this->current_user, $properties->getOwner());
    }

    public function testImportWiki(): void
    {
        $node = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <item type="wiki">
            </item>
            EOS
        );

        self::expectException(UnknownItemTypeException::class);

        $this->properties_extractor->getImportProperties($node);
    }

    public function testImportLink(): void
    {
        $node = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <item type="link">
                <properties>
                    <title>My document</title>
                </properties>
                <url>https://example.test</url>
            </item>
            EOS
        );

        $properties = $this->properties_extractor->getImportProperties($node);

        self::assertEquals(PLUGIN_DOCMAN_ITEM_TYPE_LINK, $properties->getItemTypeId());
        self::assertEquals('https://example.test', $properties->getLinkUrl());
    }

    public function testImportFolder(): void
    {
        $node = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <item type="folder">
                <properties>
                    <title>My folder</title>
                </properties>
            </item>
            EOS
        );

        $properties = $this->properties_extractor->getImportProperties($node);

        self::assertEquals(PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, $properties->getItemTypeId());
    }

    public function testImportFile(): void
    {
        $node = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <item type="file">
                <properties>
                    <title>My document</title>
                </properties>
            </item>
            EOS
        );

        $properties = $this->properties_extractor->getImportProperties($node);

        self::assertEquals(PLUGIN_DOCMAN_ITEM_TYPE_FILE, $properties->getItemTypeId());
    }

    public function testImportEmbedded(): void
    {
        $node = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <item type="embeddedfile">
                <properties>
                    <title>My document</title>
                </properties>
            </item>
            EOS
        );

        $properties = $this->properties_extractor->getImportProperties($node);

        self::assertEquals(PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE, $properties->getItemTypeId());
    }
}
