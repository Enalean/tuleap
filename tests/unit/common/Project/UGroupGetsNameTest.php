<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Project;

use ProjectUGroup;
use Tuleap\GlobalLanguageMock;

final class UGroupGetsNameTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['Language']->method('getText')
            ->willReturnMap(
                [
                    ['project_ugroup', 'ugroup_project_members', '', 'membre_de_projet'],
                    ['project_ugroup', 'ugroup_project_admins', '', 'administrateur_de_le_projet'],
                ]
            );
    }

    public function testItReturnsProjectMembers(): void
    {
        $ugroup = new ProjectUGroup(
            ['ugroup_id' => ProjectUGroup::PROJECT_MEMBERS, 'name' => 'ugroup_project_members_name_key']
        );
        self::assertEquals('ugroup_project_members_name_key', $ugroup->getName());
        self::assertEquals('membre_de_projet', $ugroup->getTranslatedName());
        self::assertEquals('project_members', $ugroup->getNormalizedName());
    }

    public function testItReturnsProjectAdmins(): void
    {
        $ugroup = new ProjectUGroup(
            ['ugroup_id' => ProjectUGroup::PROJECT_ADMIN, 'name' => 'ugroup_project_admins_name_key']
        );
        self::assertEquals('ugroup_project_admins_name_key', $ugroup->getName());
        self::assertEquals('administrateur_de_le_projet', $ugroup->getTranslatedName());
        self::assertEquals('project_admins', $ugroup->getNormalizedName());
    }

    public function testItReturnsAStaticGroup(): void
    {
        $ugroup = new ProjectUGroup(['ugroup_id' => 120, 'name' => 'Zoum_zoum_zen']);
        self::assertEquals('Zoum_zoum_zen', $ugroup->getName());
        self::assertEquals('Zoum_zoum_zen', $ugroup->getTranslatedName());
        self::assertEquals('Zoum_zoum_zen', $ugroup->getNormalizedName());
    }
}
