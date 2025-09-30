<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
class b202105171414_each_repository_integration_is_unique extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add integration project information in the table plugin_gitlab_repository_project';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $this->db->dbh->beginTransaction();
        $this->createIntegrationTable();
        $this->createIntegrationWebhookTable();
        $this->createIntegrationAPITokenTable();
        $this->createIntegrationCommitInfoTable();
        $this->createIntegrationMergeRequestInfoTable();
        $this->createIntegrationTagInfoTable();
        $this->insertRepositoryData();
        $this->insertWebhookData();
        $this->insertAPITokenData();
        $this->insertCommitData();
        $this->insertMergeRequestData();
        $this->insertTagData();
        $this->db->dbh->commit();
    }

    private function createIntegrationTable(): void
    {
        $sql = '
            CREATE TABLE IF NOT EXISTS plugin_gitlab_repository_integration (
                id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
                gitlab_repository_id INT(11) NOT NULL,
                gitlab_repository_url VARCHAR(255) NOT NULL,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                last_push_date INT(11) NOT NULL,
                project_id INT(11) NOT NULL,
                allow_artifact_closure TINYINT(1) NOT NULL DEFAULT 0,
                UNIQUE (gitlab_repository_id, gitlab_repository_url, project_id)
            ) ENGINE=InnoDB;
        ';

        $this->db->createTable('plugin_gitlab_repository_integration', $sql);

        if (! $this->db->tableNameExists('plugin_gitlab_repository_integration')) {
            $this->rollBackOnError(
                'Table plugin_gitlab_repository_integration has not been created in database'
            );
        }
    }

    private function createIntegrationWebhookTable(): void
    {
        $sql = '
            CREATE TABLE IF NOT EXISTS plugin_gitlab_repository_integration_webhook (
                integration_id INT(11) NOT NULL PRIMARY KEY,
                webhook_secret BLOB NOT NULL,
                gitlab_webhook_id INT(11) NOT NULL DEFAULT 0
            ) ENGINE=InnoDB;
        ';

        $this->db->createTable('plugin_gitlab_repository_integration_webhook', $sql);

        if (! $this->db->tableNameExists('plugin_gitlab_repository_integration_webhook')) {
            $this->rollBackOnError(
                'Table plugin_gitlab_repository_integration_webhook has not been created in database'
            );
        }
    }

    private function createIntegrationAPITokenTable(): void
    {
        $sql = '
            CREATE TABLE IF NOT EXISTS plugin_gitlab_repository_integration_token (
                integration_id INT(11) NOT NULL PRIMARY KEY,
                token BLOB NOT NULL,
                is_email_already_send_for_invalid_token BOOL NOT NULL DEFAULT FALSE
            ) ENGINE=InnoDB;
        ';

        $this->db->createTable('plugin_gitlab_repository_integration_token', $sql);

        if (! $this->db->tableNameExists('plugin_gitlab_repository_integration_token')) {
            $this->rollBackOnError(
                'Table plugin_gitlab_repository_integration_token has not been created in database'
            );
        }
    }

    private function createIntegrationCommitInfoTable(): void
    {
        $sql = '
            CREATE TABLE IF NOT EXISTS plugin_gitlab_repository_integration_commit_info (
                id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
                integration_id INT(11) NOT NULL,
                commit_sha1 BINARY(20) NOT NULL,
                commit_date INT(11) NOT NULL,
                commit_title TEXT NOT NULL,
                commit_branch VARCHAR(255) NOT NULL,
                author_name TEXT NOT NULL,
                author_email TEXT NOT NULL,
                INDEX commit_id(integration_id, commit_sha1(10))
            ) ENGINE=InnoDB;
        ';

        $this->db->createTable('plugin_gitlab_repository_integration_commit_info', $sql);

        if (! $this->db->tableNameExists('plugin_gitlab_repository_integration_commit_info')) {
            $this->rollBackOnError(
                'Table plugin_gitlab_repository_integration_commit_info has not been created in database'
            );
        }
    }

    private function createIntegrationMergeRequestInfoTable(): void
    {
        $sql = '
            CREATE TABLE IF NOT EXISTS plugin_gitlab_repository_integration_merge_request_info (
                id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
                integration_id INT(11) NOT NULL,
                merge_request_id INT(11) NOT NULL,
                title TEXT NOT NULL,
                description TEXT NOT NULL,
                state TEXT NOT NULL,
                created_at INT(11) NOT NULL,
                author_name TEXT DEFAULT NULL,
                author_email TEXT DEFAULT NULL,
                UNIQUE KEY merge_request_id(integration_id, merge_request_id)
            ) ENGINE=InnoDB;
        ';

        $this->db->createTable('plugin_gitlab_repository_integration_merge_request_info', $sql);

        if (! $this->db->tableNameExists('plugin_gitlab_repository_integration_merge_request_info')) {
            $this->rollBackOnError(
                'Table plugin_gitlab_repository_integration_merge_request_info has not been created in database'
            );
        }
    }

    private function createIntegrationTagInfoTable(): void
    {
        $sql = '
            CREATE TABLE IF NOT EXISTS plugin_gitlab_repository_integration_tag_info (
                id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
                integration_id INT(11) NOT NULL,
                commit_sha1 BINARY(20) NOT NULL,
                tag_name TEXT NOT NULL,
                tag_message TEXT NOT NULL,
                UNIQUE(integration_id, tag_name(255))
            ) ENGINE=InnoDB;
        ';

        $this->db->createTable('plugin_gitlab_repository_integration_tag_info', $sql);

        if (! $this->db->tableNameExists('plugin_gitlab_repository_integration_tag_info')) {
            $this->rollBackOnError(
                'Table plugin_gitlab_repository_integration_tag_info has not been created in database'
            );
        }
    }

    private function insertRepositoryData(): void
    {
        $sql = '
            INSERT INTO plugin_gitlab_repository_integration (gitlab_repository_id, gitlab_repository_url, name, description, last_push_date, project_id, allow_artifact_closure)
            SELECT plugin_gitlab_repository.gitlab_repository_id,
                   plugin_gitlab_repository.gitlab_repository_url,
                   plugin_gitlab_repository.name,
                   plugin_gitlab_repository.description,
                   plugin_gitlab_repository.last_push_date,
                   plugin_gitlab_repository_project.project_id,
                   plugin_gitlab_repository_project.allow_artifact_closure
            FROM plugin_gitlab_repository
                     LEFT JOIN plugin_gitlab_repository_project ON (plugin_gitlab_repository.id = plugin_gitlab_repository_project.id);
        ';

        $result = $this->db->dbh->exec($sql);
        if ($result === false) {
            $this->rollBackOnError('Could not insert data in plugin_gitlab_repository_integration table');
        }
    }

    private function insertWebhookData(): void
    {
        $sql = '
            INSERT INTO plugin_gitlab_repository_integration_webhook (integration_id, webhook_secret, gitlab_webhook_id)
            SELECT plugin_gitlab_repository_integration.id, A.webhook_secret, A.gitlab_webhook_id
            FROM plugin_gitlab_repository_integration
            INNER JOIN (
                SELECT plugin_gitlab_repository.gitlab_repository_url,
                       plugin_gitlab_repository_project.project_id,
                       plugin_gitlab_repository_webhook_secret.webhook_secret,
                       plugin_gitlab_repository_webhook_secret.gitlab_webhook_id
                FROM plugin_gitlab_repository
                         INNER JOIN plugin_gitlab_repository_project ON (plugin_gitlab_repository.id = plugin_gitlab_repository_project.id)
                         INNER JOIN plugin_gitlab_repository_webhook_secret ON (plugin_gitlab_repository.id = plugin_gitlab_repository_webhook_secret.repository_id)
                ) as A
            ON (
                A.project_id = plugin_gitlab_repository_integration.project_id AND
                A.gitlab_repository_url = plugin_gitlab_repository_integration.gitlab_repository_url
            );
        ';

        $result = $this->db->dbh->exec($sql);
        if ($result === false) {
            $this->rollBackOnError('Could not insert data in plugin_gitlab_repository_integration_webhook table');
        }
    }

    private function insertAPITokenData(): void
    {
        $sql = '
            INSERT INTO plugin_gitlab_repository_integration_token (integration_id, token, is_email_already_send_for_invalid_token)
            SELECT plugin_gitlab_repository_integration.id, A.token, A.is_email_already_send_for_invalid_token
            FROM plugin_gitlab_repository_integration
            INNER JOIN (
                SELECT plugin_gitlab_repository.gitlab_repository_url,
                       plugin_gitlab_repository_project.project_id,
                       plugin_gitlab_bot_api_token.token,
                       plugin_gitlab_bot_api_token.is_email_already_send_for_invalid_token
                FROM plugin_gitlab_repository
                         INNER JOIN plugin_gitlab_repository_project ON (plugin_gitlab_repository.id = plugin_gitlab_repository_project.id)
                         INNER JOIN plugin_gitlab_bot_api_token ON (plugin_gitlab_repository.id = plugin_gitlab_bot_api_token.repository_id)
                ) as A
            ON (
                A.project_id = plugin_gitlab_repository_integration.project_id AND
                A.gitlab_repository_url = plugin_gitlab_repository_integration.gitlab_repository_url
            );
        ';

        $result = $this->db->dbh->exec($sql);
        if ($result === false) {
            $this->rollBackOnError('Could not insert data in plugin_gitlab_repository_integration_token table');
        }
    }

    private function insertCommitData(): void
    {
        $sql = '
            INSERT INTO plugin_gitlab_repository_integration_commit_info
                (integration_id, commit_sha1, commit_date, commit_title, commit_branch, author_name, author_email)
            SELECT plugin_gitlab_repository_integration.id,
                   A.commit_sha1,
                   A.commit_date,
                   A.commit_title,
                   A.commit_branch,
                   A.author_name,
                   A.author_email
            FROM plugin_gitlab_repository_integration
            INNER JOIN (
                SELECT plugin_gitlab_repository.gitlab_repository_url,
                       plugin_gitlab_repository_project.project_id,
                       plugin_gitlab_commit_info.commit_sha1,
                       plugin_gitlab_commit_info.commit_date,
                       plugin_gitlab_commit_info.commit_title,
                       plugin_gitlab_commit_info.commit_branch,
                       plugin_gitlab_commit_info.author_name,
                       plugin_gitlab_commit_info.author_email
                FROM plugin_gitlab_repository
                         INNER JOIN plugin_gitlab_repository_project ON (plugin_gitlab_repository.id = plugin_gitlab_repository_project.id)
                         INNER JOIN plugin_gitlab_commit_info ON (plugin_gitlab_repository.id = plugin_gitlab_commit_info.repository_id)
                ) as A
            ON (
                A.project_id = plugin_gitlab_repository_integration.project_id AND
                A.gitlab_repository_url = plugin_gitlab_repository_integration.gitlab_repository_url
            );
        ';

        $result = $this->db->dbh->exec($sql);
        if ($result === false) {
            $this->rollBackOnError('Could not insert data in plugin_gitlab_repository_integration_commit_info table');
        }
    }

    private function insertMergeRequestData(): void
    {
        $sql = '
            INSERT INTO plugin_gitlab_repository_integration_merge_request_info
                (integration_id, merge_request_id, title, description, state, created_at, author_name, author_email)
            SELECT plugin_gitlab_repository_integration.id,
                   A.merge_request_id,
                   A.title,
                   A.description,
                   A.state,
                   A.created_at,
                   A.author_name,
                   A.author_email
            FROM plugin_gitlab_repository_integration
            INNER JOIN (
                SELECT plugin_gitlab_repository.gitlab_repository_url,
                       plugin_gitlab_repository_project.project_id,
                       plugin_gitlab_merge_request_info.merge_request_id,
                       plugin_gitlab_merge_request_info.title,
                       plugin_gitlab_merge_request_info.description,
                       plugin_gitlab_merge_request_info.state,
                       plugin_gitlab_merge_request_info.created_at,
                       plugin_gitlab_merge_request_info.author_name,
                       plugin_gitlab_merge_request_info.author_email
                FROM plugin_gitlab_repository
                         INNER JOIN plugin_gitlab_repository_project ON (plugin_gitlab_repository.id = plugin_gitlab_repository_project.id)
                         INNER JOIN plugin_gitlab_merge_request_info ON (plugin_gitlab_repository.id = plugin_gitlab_merge_request_info.repository_id)
                ) as A
            ON (
                A.project_id = plugin_gitlab_repository_integration.project_id AND
                A.gitlab_repository_url = plugin_gitlab_repository_integration.gitlab_repository_url
            );
        ';

        $result = $this->db->dbh->exec($sql);
        if ($result === false) {
            $this->rollBackOnError('Could not insert data in plugin_gitlab_repository_integration_merge_request_info table');
        }
    }

    private function insertTagData(): void
    {
        $sql = '
            INSERT INTO plugin_gitlab_repository_integration_tag_info
                (integration_id, commit_sha1, tag_name, tag_message)
            SELECT plugin_gitlab_repository_integration.id,
                   A.commit_sha1,
                   A.tag_name,
                   A.tag_message
            FROM plugin_gitlab_repository_integration
            INNER JOIN (
                SELECT plugin_gitlab_repository.gitlab_repository_url,
                       plugin_gitlab_repository_project.project_id,
                       plugin_gitlab_tag_info.commit_sha1,
                       plugin_gitlab_tag_info.tag_name,
                       plugin_gitlab_tag_info.tag_message
                FROM plugin_gitlab_repository
                         INNER JOIN plugin_gitlab_repository_project ON (plugin_gitlab_repository.id = plugin_gitlab_repository_project.id)
                         INNER JOIN plugin_gitlab_tag_info ON (plugin_gitlab_repository.id = plugin_gitlab_tag_info.repository_id)
                ) as A
            ON (
                A.project_id = plugin_gitlab_repository_integration.project_id AND
                A.gitlab_repository_url = plugin_gitlab_repository_integration.gitlab_repository_url
            );
        ';

        $result = $this->db->dbh->exec($sql);
        if ($result === false) {
            $this->rollBackOnError('Could not insert data in plugin_gitlab_repository_integration_tag_info table');
        }
    }

    private function rollBackOnError(string $message): void
    {
        $this->db->dbh->rollBack();
        throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException($message);
    }
}
