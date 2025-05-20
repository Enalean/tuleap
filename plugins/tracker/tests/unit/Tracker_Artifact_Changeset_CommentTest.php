<?php
/**
 * Copyright (c) Enalean, 2015 - present. All Rights Reserved.
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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_Artifact_Changeset_CommentTest extends \Tuleap\Test\PHPUnit\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Tuleap\GlobalLanguageMock;

    private string $timestamp = '1433863107';

    private Tracker_Artifact_Changeset $changeset;
    private UserManager&MockObject $user_manager;

    protected function setUp(): void
    {
        $user = UserTestBuilder::aUser()
            ->withId(101)
            ->withLdapId('ldap_01')
            ->withAvatarUrl('/path/to/avatar.png')
            ->build();

        $this->changeset = ChangesetTestBuilder::aChangeset(1001)->build();
        $this->timestamp = '1433863107';

        $this->user_manager = $this->createMock(UserManager::class);
        $this->user_manager->method('getUserById')->with(101)->willReturn($user);
        $this->user_manager->method('getCurrentUser')->willReturn($user);

        $GLOBALS['Language']->method('getText')->willReturn('');
        UserManager::setInstance($this->user_manager);
    }

    protected function tearDown(): void
    {
        UserManager::clearInstance();
        Codendi_HTMLPurifier::clearInstance();
    }

    public function testItExportsToXMLWithoutPrivateUGroupsIfNoUGroup(): void
    {
        $body = '<b> My comment 01</b>';

        $comment = new Tracker_Artifact_Changeset_Comment(
            1,
            $this->changeset,
            0,
            0,
            101,
            $this->timestamp,
            $body,
            'html',
            0,
            []
        );

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
                <comments/>';

        $changeset_node    = new SimpleXMLElement($xml);
        $user_xml_exporter = new UserXMLExporter($this->user_manager, $this->stubCollection());

        $comment->exportToXML($changeset_node, $user_xml_exporter);

        $this->assertNotNull($changeset_node->comment);
        $this->assertNotNull($changeset_node->comment->submitted_by);
        $this->assertNotNull($changeset_node->comment->submitted_on);
        $this->assertNotNull($changeset_node->comment->body);

        $this->assertEquals((string) $changeset_node->comment->submitted_by, 'ldap_01');
        $this->assertEquals($changeset_node->comment->submitted_by['format'], 'ldap');

        $this->assertEquals((string) $changeset_node->comment->submitted_on, '2015-06-09T17:18:27+02:00');
        $this->assertEquals($changeset_node->comment->submitted_on['format'], 'ISO8601');

        $this->assertEquals((string) $changeset_node->comment->body, $body);
        $this->assertEquals($changeset_node->comment->body['format'], 'html');
        $this->assertFalse(isset($changeset_node->comment->private_ugroups));
    }

    private function stubCollection(): \UserXMLExportedCollection
    {
        $collection = $this->createMock(\UserXMLExportedCollection::class);
        $collection->method('add');

        return $collection;
    }

    public function testItExportsToXMLWithoutPrivateUGroupsIfUgroupIsNull(): void
    {
        $body = '<b> My comment 01</b>';

        $comment = new Tracker_Artifact_Changeset_Comment(
            1,
            $this->changeset,
            0,
            0,
            101,
            $this->timestamp,
            $body,
            'html',
            0,
            null
        );

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
                <comments/>';

        $changeset_node    = new SimpleXMLElement($xml);
        $user_xml_exporter = new UserXMLExporter($this->user_manager, $this->stubCollection());

        $comment->exportToXML($changeset_node, $user_xml_exporter);

        $this->assertNotNull($changeset_node->comment);
        $this->assertNotNull($changeset_node->comment->submitted_by);
        $this->assertNotNull($changeset_node->comment->submitted_on);
        $this->assertNotNull($changeset_node->comment->body);

        $this->assertEquals((string) $changeset_node->comment->submitted_by, 'ldap_01');
        $this->assertEquals($changeset_node->comment->submitted_by['format'], 'ldap');

        $this->assertEquals((string) $changeset_node->comment->submitted_on, '2015-06-09T17:18:27+02:00');
        $this->assertEquals($changeset_node->comment->submitted_on['format'], 'ISO8601');

        $this->assertEquals((string) $changeset_node->comment->body, $body);
        $this->assertEquals($changeset_node->comment->body['format'], 'html');
        $this->assertFalse(isset($changeset_node->comment->private_ugroups));
    }

    public function testItExportsToXMLWithPrivateUGroups(): void
    {
        $ugroup_1 = $this->createMock(ProjectUGroup::class);
        $ugroup_1->method('getNormalizedName')->willReturn('ugroup_1');
        $ugroup_2 = $this->createMock(ProjectUGroup::class);
        $ugroup_2->method('getNormalizedName')->willReturn('ugroup_2');
        $body = '<b> My comment 01</b>';

        $comment = new Tracker_Artifact_Changeset_Comment(
            1,
            $this->changeset,
            0,
            0,
            101,
            $this->timestamp,
            $body,
            'html',
            0,
            [$ugroup_1, $ugroup_2]
        );

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
                <comments/>';

        $changeset_node    = new SimpleXMLElement($xml);
        $user_xml_exporter = new UserXMLExporter($this->user_manager, $this->stubCollection());

        $comment->exportToXML($changeset_node, $user_xml_exporter);

        $this->assertNotNull($changeset_node->comment);
        $this->assertNotNull($changeset_node->comment->submitted_by);
        $this->assertNotNull($changeset_node->comment->submitted_on);
        $this->assertNotNull($changeset_node->comment->body);

        $this->assertEquals((string) $changeset_node->comment->submitted_by, 'ldap_01');
        $this->assertEquals($changeset_node->comment->submitted_by['format'], 'ldap');

        $this->assertEquals((string) $changeset_node->comment->submitted_on, '2015-06-09T17:18:27+02:00');
        $this->assertEquals($changeset_node->comment->submitted_on['format'], 'ISO8601');

        $this->assertEquals((string) $changeset_node->comment->body, $body);
        $this->assertEquals($changeset_node->comment->body['format'], 'html');
        $this->assertTrue(isset($changeset_node->comment->private_ugroups));
        $this->assertCount(2, $changeset_node->comment->private_ugroups->ugroup);
        $this->assertEquals('ugroup_1', (string) $changeset_node->comment->private_ugroups->ugroup[0]);
        $this->assertEquals('ugroup_2', (string) $changeset_node->comment->private_ugroups->ugroup[1]);
    }

    public function testItExportsToXMLWithCrossReferencesEscaped(): void
    {
        $body         = 'See art #290';
        $escaped_body = 'See art # 290';

        $comment = new Tracker_Artifact_Changeset_Comment(
            1,
            $this->changeset,
            0,
            0,
            101,
            $this->timestamp,
            $body,
            'html',
            0,
            []
        );

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
                <comments/>';

        $changeset_node    = new SimpleXMLElement($xml);
        $user_xml_exporter = new UserXMLExporter($this->user_manager, $this->stubCollection());

        $comment->exportToXML($changeset_node, $user_xml_exporter);

        $this->assertNotNull($changeset_node->comment);
        $this->assertNotNull($changeset_node->comment->submitted_by);
        $this->assertNotNull($changeset_node->comment->submitted_on);
        $this->assertNotNull($changeset_node->comment->body);

        $this->assertEquals((string) $changeset_node->comment->submitted_by, 'ldap_01');
        $this->assertEquals($changeset_node->comment->submitted_by['format'], 'ldap');

        $this->assertEquals((string) $changeset_node->comment->submitted_on, '2015-06-09T17:18:27+02:00');
        $this->assertEquals($changeset_node->comment->submitted_on['format'], 'ISO8601');

        $this->assertEquals((string) $changeset_node->comment->body, $escaped_body);
        $this->assertEquals($changeset_node->comment->body['format'], 'html');
        $this->assertFalse(isset($changeset_node->comment->private_ugroups));
    }

    public function testItReturnsEmptyWhenFormatIsTextAndWhenItHasEmptyBody(): void
    {
        $comment = new Tracker_Artifact_Changeset_Comment(
            1,
            $this->changeset,
            0,
            0,
            101,
            $this->timestamp,
            '',
            'text',
            0,
            [],
        );

        $this->assertEquals('', $comment->fetchMailFollowUp('text'));
    }

    public function testItClearsComment(): void
    {
        $comment = new Tracker_Artifact_Changeset_Comment(
            1,
            $this->changeset,
            0,
            0,
            101,
            $this->timestamp,
            ' ',
            'html',
            1234,
            [],
        );

        $this->assertStringContainsString('Comment has been cleared', $comment->fetchMailFollowUp());
    }

    public function testItDisplayStandardComment(): void
    {
        $changeset = $this->changeset;
        $body      = 'See art #290';
        $comment   = new Tracker_Artifact_Changeset_Comment(
            1,
            $changeset,
            0,
            0,
            101,
            $this->timestamp,
            $body,
            'html',
            0,
            [],
        );
        $purifier  = $this->createMock(Codendi_HTMLPurifier::class);
        $purifier->method('purify');
        $purifier->method('purifyHTMLWithReferences')->willReturn($body);
        Codendi_HTMLPurifier::setInstance($purifier);

        $follow_up = $comment->fetchMailFollowUp();
        $this->assertStringNotContainsString('Comment has been cleared', $follow_up);
        $this->assertStringNotContainsString('Updated comment', $follow_up);
    }

    public function testItDisplayEditedComment(): void
    {
        $changeset = $this->changeset;
        $body      = 'See art #290';
        $comment   = new Tracker_Artifact_Changeset_Comment(
            1,
            $changeset,
            0,
            0,
            101,
            $this->timestamp,
            $body,
            'html',
            1234,
            [],
        );
        $purifier  = $this->createMock(Codendi_HTMLPurifier::class);
        $purifier->method('purify');
        $purifier->method('purifyHTMLWithReferences')->willReturn($body);
        Codendi_HTMLPurifier::setInstance($purifier);

        $this->assertStringContainsString('Updated comment', $comment->fetchMailFollowUp());
    }
}
