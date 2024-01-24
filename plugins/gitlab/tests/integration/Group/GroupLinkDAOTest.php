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

namespace Tuleap\Gitlab\Group;

use Tuleap\DB\DBFactory;
use Tuleap\Gitlab\API\Group\GitlabGroupApiDataRepresentation;
use Tuleap\Gitlab\Group\Token\GroupLinkApiTokenDAO;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

final class GroupLinkDAOTest extends TestIntegrationTestCase
{
    private const GITLAB_GROUP_ID                = 99;
    private const NAME                           = 'lamany';
    private const FULL_PATH                      = 'sheikly/lamany';
    private const WEB_URL                        = 'https://gitlab.example.com/' . self::FULL_PATH;
    private const AVATAR_URL                     = 'https://gitlab.example.com/uploads/-/system/group/avatar/99/avatar.png';
    private const PROJECT_ID                     = 113;
    private const LAST_SYNCHRONIZATION_TIMESTAMP = 1685019983;
    private const BRANCH_PREFIX                  = 'dev-';
    private const FIRST_REPOSITORY_ID            = 117;
    private const SECOND_REPOSITORY_ID           = 267;
    private const ENCRYPTED_TOKEN                = 'OxFA97D2DFD016C0E9E42E';

    private GroupLinkDAO $group_dao;
    private GroupLinkRepositoryIntegrationDAO $integrations_dao;
    private GroupLinkApiTokenDAO $token_dao;
    private \Project $project;

    protected function setUp(): void
    {
        $this->group_dao        = new GroupLinkDAO();
        $this->integrations_dao = new GroupLinkRepositoryIntegrationDAO();
        $this->token_dao        = new GroupLinkApiTokenDAO();
        $this->project          = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();
    }

    public function testCRUD(): void
    {
        $group_link_id      = $this->saveAndRetrieveGroupLink();
        $updated_group_link = $this->updateAndRetrieveGroupLink($group_link_id);
        $this->addTokenToGroupLink($updated_group_link);
        $this->addRepositoryIntegrationsToGroupLink($updated_group_link);
        $this->updateSynchronizationDate($updated_group_link);
        $this->deleteGroupLink($updated_group_link);
    }

    private function saveAndRetrieveGroupLink(): int
    {
        $new_group = NewGroupLink::fromAPIRepresentation(
            GitlabGroupApiDataRepresentation::buildGitlabGroupFromApi([
                'id'         => self::GITLAB_GROUP_ID,
                'name'       => self::NAME,
                'full_path'  => self::FULL_PATH,
                'web_url'    => self::WEB_URL,
                'avatar_url' => self::AVATAR_URL,
            ]),
            $this->project,
            new \DateTimeImmutable('@' . self::LAST_SYNCHRONIZATION_TIMESTAMP),
            false,
            ''
        );

        $group_link_id = $this->group_dao->addNewGroup($new_group);

        self::assertTrue($this->group_dao->isGroupAlreadyLinked(self::GITLAB_GROUP_ID));
        self::assertTrue($this->group_dao->isProjectAlreadyLinked(self::PROJECT_ID));

        $retrieved_group_link = $this->group_dao->retrieveGroupLink($group_link_id);
        self::assertNotNull($retrieved_group_link);
        self::assertSame(self::GITLAB_GROUP_ID, $retrieved_group_link->gitlab_group_id);
        self::assertSame(self::PROJECT_ID, $retrieved_group_link->project_id);
        self::assertSame(self::NAME, $retrieved_group_link->name);
        self::assertSame(
            self::LAST_SYNCHRONIZATION_TIMESTAMP,
            $retrieved_group_link->last_synchronization_date->getTimestamp()
        );
        self::assertSame(self::FULL_PATH, $retrieved_group_link->full_path);
        self::assertSame(self::WEB_URL, $retrieved_group_link->web_url);
        self::assertSame(self::AVATAR_URL, $retrieved_group_link->avatar_url);
        self::assertFalse($retrieved_group_link->allow_artifact_closure);
        self::assertSame('', $retrieved_group_link->prefix_branch_name);

        return $group_link_id;
    }

    private function updateAndRetrieveGroupLink(int $group_link_id): GroupLink
    {
        $this->group_dao->updateArtifactClosureOfGroupLink($group_link_id, true);
        $this->group_dao->updateBranchPrefixOfGroupLink($group_link_id, self::BRANCH_PREFIX);

        $updated_group_link = $this->group_dao->retrieveGroupLinkedToProject($this->project);
        self::assertNotNull($updated_group_link);
        self::assertTrue($updated_group_link->allow_artifact_closure);
        self::assertSame(self::BRANCH_PREFIX, $updated_group_link->prefix_branch_name);

        return $updated_group_link;
    }

    private function addTokenToGroupLink(GroupLink $group_link): void
    {
        $this->token_dao->storeToken($group_link->id, self::ENCRYPTED_TOKEN);
    }

    private function addRepositoryIntegrationsToGroupLink(GroupLink $group_link): void
    {
        $this->integrations_dao->linkARepositoryIntegrationToAGroup(
            new NewRepositoryIntegrationLinkedToAGroup(self::FIRST_REPOSITORY_ID, $group_link->id)
        );
        $this->integrations_dao->linkARepositoryIntegrationToAGroup(
            new NewRepositoryIntegrationLinkedToAGroup(self::SECOND_REPOSITORY_ID, $group_link->id)
        );

        self::assertSame(2, $this->integrations_dao->countIntegratedRepositories($group_link));
    }

    private function deleteGroupLink(GroupLink $group_link): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $this->group_dao->deleteGroupLink($group_link);
        self::assertSame(0, $this->integrations_dao->countIntegratedRepositories($group_link));
        self::assertCount(
            0,
            $db->run('SELECT NULL FROM plugin_gitlab_group_token WHERE group_id = ?', $group_link->id)
        );
        self::assertFalse($this->group_dao->isGroupAlreadyLinked($group_link->gitlab_group_id));
        self::assertFalse($this->group_dao->isProjectAlreadyLinked($group_link->project_id));
        self::assertNull($this->group_dao->retrieveGroupLink($group_link->id));
    }

    private function updateSynchronizationDate(GroupLink $group_link): void
    {
        $new_last_sync_date = 1685106363;
        $this->group_dao->updateSynchronizationDate($group_link, new \DateTimeImmutable('@' . $new_last_sync_date));
        $updated_group_link = $this->group_dao->retrieveGroupLink($group_link->id);
        self::assertSame($new_last_sync_date, $updated_group_link?->last_synchronization_date->getTimestamp());
    }
}
