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

namespace Tuleap\Test\Builders;

use Tuleap\User\UserGroup\NameTranslator;

final class ProjectUGroupTestBuilder
{
    private string $name = 'My group';
    /**
     * @psalm-var list<\PFUser>
     */
    private array $users = [];

    private function __construct(private int $id)
    {
    }

    public static function aCustomUserGroup(int $user_group_id): self
    {
        if ($user_group_id < 100) {
            throw new \LogicException('User Group id must be >= 100 for custom user groups');
        }

        return new self($user_group_id);
    }

    public static function buildAnonymous(): \ProjectUGroup
    {
        return new \ProjectUgroup([
            'ugroup_id' => \ProjectUGroup::ANONYMOUS,
            'name'      => NameTranslator::ANON,
        ]);
    }

    public static function buildRegistered(): \ProjectUGroup
    {
        return new \ProjectUgroup([
            'ugroup_id' => \ProjectUGroup::REGISTERED,
            'name'      => NameTranslator::REGISTERED,
        ]);
    }

    public static function buildAuthenticated(): \ProjectUGroup
    {
        return new \ProjectUgroup([
            'ugroup_id' => \ProjectUGroup::AUTHENTICATED,
            'name'      => NameTranslator::AUTHENTICATED,
        ]);
    }

    public static function buildNobody(): \ProjectUGroup
    {
        return new \ProjectUgroup([
            'ugroup_id' => \ProjectUGroup::NONE,
            'name'      => NameTranslator::NOBODY,
        ]);
    }

    public static function buildProjectMembers(): \ProjectUGroup
    {
        return new \ProjectUgroup([
            'ugroup_id' => \ProjectUGroup::PROJECT_MEMBERS,
            'name'      => NameTranslator::PROJECT_MEMBERS,
        ]);
    }

    public static function buildProjectMembersWith(\PFUser $user, \PFUser ...$other_users): \ProjectUGroup
    {
        $group = self::buildProjectMembers();
        $group->setMembers($user, ...$other_users);
        return $group;
    }

    public static function buildProjectAdmins(): \ProjectUGroup
    {
        return new \ProjectUgroup([
            'ugroup_id' => \ProjectUGroup::PROJECT_ADMIN,
            'name'      => NameTranslator::PROJECT_ADMINS,
        ]);
    }

    public function withName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function withUsers(\PFUser $user, \PFUser ...$other_users): self
    {
        $this->users = [$user, ...$other_users];

        return $this;
    }

    public function build(): \ProjectUGroup
    {
        $ugroup = new \ProjectUGroup(['ugroup_id' => $this->id, 'name' => $this->name]);
        $ugroup->setMembers(...$this->users);
        return $ugroup;
    }
}
