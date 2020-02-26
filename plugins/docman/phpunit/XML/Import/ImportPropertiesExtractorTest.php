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

use Mockery;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use User\XML\Import\IFindUserFromXMLReference;

class ImportPropertiesExtractorTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var \DateTimeImmutable
     */
    private $current_date;
    /**
     * @var ImportPropertiesExtractor
     */
    private $properties_extractor;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|IFindUserFromXMLReference
     */
    private $user_finder;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\PFUser
     */
    private $current_user;

    protected function setUp(): void
    {
        $this->current_date = new \DateTimeImmutable();
        $this->user_finder  = Mockery::mock(IFindUserFromXMLReference::class);
        $this->current_user = Mockery::mock(\PFUser::class);

        $this->properties_extractor = new ImportPropertiesExtractor($this->current_date, $this->current_user, $this->user_finder);
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

        $this->assertEquals(PLUGIN_DOCMAN_ITEM_TYPE_EMPTY, $properties->getItemTypeId());
        $this->assertEquals('My document', $properties->getTitle());
        $this->assertEquals('', $properties->getDescription());
        $this->assertEquals($this->current_date, $properties->getCreateDate());
        $this->assertEquals($this->current_date, $properties->getUpdateDate());
        $this->assertEquals($this->current_user, $properties->getOwner());
        $this->assertNull($properties->getLinkUrl());
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

        $this->assertEquals('Lorem ipsum', $properties->getDescription());
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

        $this->assertEquals(
            (new \DateTimeImmutable())->setTimestamp(1234567890),
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

        $expected_update_date = (new \DateTimeImmutable())->setTimestamp(1234567890);
        $this->assertEquals($expected_update_date, $properties->getUpdateDate());
        $this->assertEquals($expected_update_date, $properties->getCreateDate());
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

        $this->assertEquals(
            (new \DateTimeImmutable())->setTimestamp(1234567890),
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

        $this->assertEquals(
            (new \DateTimeImmutable())->setTimestamp(1234567890),
            $properties->getCreateDate()
        );
        $this->assertEquals(
            (new \DateTimeImmutable())->setTimestamp(1324567890),
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

        $user = Mockery::mock(\PFUser::class);
        $this->user_finder
            ->shouldReceive('getUser')
            ->once()
            ->andReturn($user);

        $properties = $this->properties_extractor->getImportProperties($node);

        $this->assertEquals($user, $properties->getOwner());
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

        $this->expectException(UnknownItemTypeException::class);

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

        $this->assertEquals(PLUGIN_DOCMAN_ITEM_TYPE_LINK, $properties->getItemTypeId());
        $this->assertEquals('https://example.test', $properties->getLinkUrl());
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

        $this->assertEquals(PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, $properties->getItemTypeId());
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

        $this->assertEquals(PLUGIN_DOCMAN_ITEM_TYPE_FILE, $properties->getItemTypeId());
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

        $this->assertEquals(PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE, $properties->getItemTypeId());
    }
}
