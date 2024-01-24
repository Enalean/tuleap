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

namespace Tuleap\Gitlab\Repository;

use ParagonIE\EasyDB\EasyDB;
use Tuleap\DB\DBFactory;
use Tuleap\Gitlab\Group\GroupLinkRepositoryIntegrationDAO;
use Tuleap\Gitlab\Group\NewRepositoryIntegrationLinkedToAGroup;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class GitlabRepositoryIntegrationDaoTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const GITLAB_REPOSITORY_ID = 23;
    private const NAME                 = 'sloeberry';
    private const DESCRIPTION          = 'vestrymanly biophagous stitch minify hebetic unpooled clipei lennow';
    private const WEB_URL              = 'https://gitlab.example.com/quadriradiate/' . self::NAME;
    private const LAST_PUSH_TIMESTAMP  = 1462224394;
    private const PROJECT_ID           = 119;
    private const GROUP_LINK_ID        = 4;

    private GitlabRepositoryIntegrationDao $repository_dao;
    private GroupLinkRepositoryIntegrationDAO $group_repositories_dao;

    protected function setUp(): void
    {
        $this->repository_dao         = new GitlabRepositoryIntegrationDao();
        $this->group_repositories_dao = new GroupLinkRepositoryIntegrationDAO();
    }

    protected function tearDown(): void
    {
        $this->getDB()->run('DELETE FROM plugin_gitlab_group_repository_integration');
        $this->getDB()->run('DELETE FROM plugin_gitlab_repository_integration');
    }

    private function getDB(): EasyDB
    {
        return DBFactory::getMainTuleapDBConnection()->getDB();
    }

    public function testCRUD(): void
    {
        $integration_id      = $this->saveAndRetrieveIntegration();
        $updated_integration = $this->updateAndRetrieveIntegration($integration_id);
        $this->deleteRepositoryIntegration($updated_integration);
    }

    private function saveAndRetrieveIntegration(): int
    {
        $integration_id = $this->repository_dao->createGitlabRepositoryIntegration(
            self::GITLAB_REPOSITORY_ID,
            self::NAME,
            self::DESCRIPTION,
            self::WEB_URL,
            self::LAST_PUSH_TIMESTAMP,
            self::PROJECT_ID,
            false,
        );

        self::assertTrue(
            $this->repository_dao->isAGitlabRepositoryWithSameNameAlreadyIntegratedInProject(
                self::NAME,
                'https://gitlab.example.com/different-url',
                self::PROJECT_ID
            )
        );
        self::assertTrue(
            $this->repository_dao->isTheGitlabRepositoryAlreadyIntegratedInProject(
                self::PROJECT_ID,
                self::GITLAB_REPOSITORY_ID,
                self::WEB_URL
            )
        );

        $retrieved_integration = $this->retrieveIntegration($integration_id);

        self::assertSame(self::GITLAB_REPOSITORY_ID, $retrieved_integration->getGitlabRepositoryId());
        self::assertSame(self::PROJECT_ID, (int) $retrieved_integration->getProject()->getID());
        self::assertSame(self::NAME, $retrieved_integration->getName());
        self::assertSame(self::DESCRIPTION, $retrieved_integration->getDescription());
        self::assertSame(self::LAST_PUSH_TIMESTAMP, $retrieved_integration->getLastPushDate()->getTimestamp());
        self::assertSame(self::WEB_URL, $retrieved_integration->getGitlabRepositoryUrl());
        self::assertFalse($retrieved_integration->isArtifactClosureAllowed());

        return $integration_id;
    }

    private function retrieveIntegration(int $integration_id): GitlabRepositoryIntegration
    {
        $row = $this->repository_dao->searchIntegrationById($integration_id);
        if ($row === null) {
            throw new \Exception("Expected to retrieve integration #{$integration_id}");
        }
        $project = ProjectTestBuilder::aProject()->withId($row['project_id'])->build();
        return new GitlabRepositoryIntegration(
            $row['id'],
            $row['gitlab_repository_id'],
            $row['name'],
            $row['description'],
            $row['gitlab_repository_url'],
            (new \DateTimeImmutable('@' . $row['last_push_date'])),
            $project,
            (bool) $row['allow_artifact_closure']
        );
    }

    private function updateAndRetrieveIntegration(int $integration_id): GitlabRepositoryIntegration
    {
        $this->repository_dao->updateGitlabRepositoryIntegrationAllowArtifactClosureValue($integration_id, true);
        $update_1_integration = $this->retrieveIntegration($integration_id);
        self::assertTrue($update_1_integration->isArtifactClosureAllowed());


        $new_last_push_timestamp = 1500069612;
        $this->repository_dao->updateLastPushDateForIntegration($integration_id, $new_last_push_timestamp);
        $update_2_integration = $this->retrieveIntegration($integration_id);
        self::assertSame($new_last_push_timestamp, $update_2_integration->getLastPushDate()->getTimestamp());

        return $update_2_integration;
    }

    private function deleteRepositoryIntegration(GitlabRepositoryIntegration $integration): void
    {
        $integration_id = $integration->getId();
        $this->addRepositoryIntegrationToGroupLink($integration);

        $this->repository_dao->deleteIntegration($integration_id);

        self::assertFalse(
            $this->group_repositories_dao->isRepositoryIntegrationAlreadyLinkedToAGroup($integration_id)
        );
        self::assertFalse(
            $this->repository_dao->isAGitlabRepositoryWithSameNameAlreadyIntegratedInProject(
                self::NAME,
                'https://gitlab.example.com/different-url',
                self::PROJECT_ID
            )
        );
        self::assertFalse(
            $this->repository_dao->isTheGitlabRepositoryAlreadyIntegratedInProject(
                self::PROJECT_ID,
                self::GITLAB_REPOSITORY_ID,
                self::WEB_URL
            )
        );
        self::assertNull($this->repository_dao->searchIntegrationById($integration_id));
    }

    private function addRepositoryIntegrationToGroupLink(GitlabRepositoryIntegration $integration): void
    {
        $this->group_repositories_dao->linkARepositoryIntegrationToAGroup(
            new NewRepositoryIntegrationLinkedToAGroup($integration->getId(), self::GROUP_LINK_ID)
        );
    }
}
