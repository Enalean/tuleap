<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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
use Tuleap\PHPWiki\WikiPage;
use Tuleap\Project\UGroupLiteralizer;
use Tuleap\Test\PHPUnit\TestCase;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
final class Wiki_PermissionsManagerTest extends TestCase
{
    private Wiki_PermissionsManager $wiki_permissions_manager;
    private PermissionsManager&MockObject $permission_manager;
    private WikiPage&MockObject $wiki_page;
    private Project&MockObject $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = $this->createMock(Project::class);
        $this->project->method('getUnixName')->willReturn('perceval');
        $this->project->method('getId')->willReturn(200);

        $this->wiki_page = $this->createMock(WikiPage::class);
        $this->wiki_page->method('getId')->willReturn(101);
        $this->wiki_page->method('getGid')->willReturn(200);

        $this->permission_manager = $this->createMock(PermissionsManager::class);
        $project_manager          = $this->createMock(ProjectManager::class);
        $project_manager->method('getProject')->with(200)->willReturn($this->project);

        $this->wiki_permissions_manager = new Wiki_PermissionsManager(
            $this->permission_manager,
            $project_manager,
            new UGroupLiteralizer(),
        );
    }

    public function testItReturnsPageRights(): void
    {
        $this->permission_manager->method('getAuthorizedUgroupIds')->willReturnCallback(
            function (int $object_id, string $permission_type): array {
                if ($object_id === 101 && $permission_type === 'WIKIPAGE_READ') {
                    return ['3', '4', '14', '107'];
                } elseif ($object_id === 200 && $permission_type === 'WIKI_READ') {
                    return ['2'];
                } else {
                    throw new LogicException('must not be here');
                }
            }
        );

        $this->project->method('isPublic')->willReturn(true);

        $expected = [
            '@perceval_project_members', '@perceval_project_admin', '@perceval_wiki_admin', '@ug_107',
        ];

        self::assertEquals(
            $expected,
            $this->wiki_permissions_manager->getFromattedUgroupsThatCanReadWikiPage($this->wiki_page)
        );
    }

    public function testItReturnsServiceRightsIfPageRightsAreWeeker(): void
    {
        $this->permission_manager->method('getAuthorizedUgroupIds')->willReturnCallback(
            function (int $object_id, string $permission_type): array {
                if ($object_id === 101 && $permission_type === 'WIKIPAGE_READ') {
                    return ['3'];
                } elseif ($object_id === 200 && $permission_type === 'WIKI_READ') {
                    return ['4', '14', '107'];
                } else {
                    throw new LogicException('must not be here');
                }
            }
        );

        $this->project->method('isPublic')->willReturn(true);

        $expected = [
            '@perceval_project_admin', '@perceval_wiki_admin', '@ug_107',
        ];

        self::assertEquals(
            $expected,
            $this->wiki_permissions_manager->getFromattedUgroupsThatCanReadWikiPage($this->wiki_page)
        );
    }

    public function testItReturnsMixedServiceAndPageRights(): void
    {
        $this->permission_manager->method('getAuthorizedUgroupIds')->willReturnCallback(
            function (int $object_id, string $permission_type): array {
                if ($object_id === 101 && $permission_type === 'WIKIPAGE_READ') {
                    return ['107', '108', '4'];
                } elseif ($object_id === 200 && $permission_type === 'WIKI_READ') {
                    return ['14', '106'];
                } else {
                    throw new LogicException('must not be here');
                }
            }
        );

        $this->project->method('isPublic')->willReturn(true);

        $expected = [
            '@perceval_wiki_admin', '@ug_106', '@perceval_project_admin',
        ];

        self::assertEquals(
            $expected,
            $this->wiki_permissions_manager->getFromattedUgroupsThatCanReadWikiPage($this->wiki_page)
        );
    }

    public function testItDoesNotReturnNonMemberUgroupsIfProjectIsPrivate(): void
    {
        $this->permission_manager->method('getAuthorizedUgroupIds')->willReturnCallback(
            function (int $object_id, string $permission_type): array {
                if ($object_id === 101 && $permission_type === 'WIKIPAGE_READ') {
                    return ['2'];
                } elseif ($object_id === 200 && $permission_type === 'WIKI_READ') {
                    return ['2'];
                } else {
                    throw new LogicException('must not be here');
                }
            }
        );

        $this->project->method('isPublic')->willReturn(false);

        $expected = [
            '@perceval_project_admin', '@perceval_wiki_admin',
        ];

        self::assertEquals(
            $expected,
            $this->wiki_permissions_manager->getFromattedUgroupsThatCanReadWikiPage($this->wiki_page)
        );
    }

    public function testItAlwaysReturnsWikiAndProjectAdminGroups(): void
    {
        $this->permission_manager->method('getAuthorizedUgroupIds')->willReturnCallback(
            function (int $object_id, string $permission_type): array {
                if ($object_id === 101 && $permission_type === 'WIKIPAGE_READ') {
                    return ['107'];
                } elseif ($object_id === 200 && $permission_type === 'WIKI_READ') {
                    return ['3'];
                } else {
                    throw new LogicException('must not be here');
                }
            }
        );

        $this->project->method('isPublic')->willReturn(true);

        $expected = [
            '@ug_107', '@perceval_project_admin', '@perceval_wiki_admin',
        ];

        self::assertEquals(
            $expected,
            $this->wiki_permissions_manager->getFromattedUgroupsThatCanReadWikiPage($this->wiki_page)
        );
    }
}
