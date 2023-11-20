<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

declare(strict_types=1);

namespace Tuleap\PullRequest\REST;

require_once __DIR__ . '/../../../../tests/lib/DatabaseInitialisation.class.php';

use ForgeConfig;
use Tuleap\PullRequest\FeatureFlagEditComments;

final class DatabaseInitialization extends \DatabaseInitialization
{
    public function setUp(): void
    {
        $this->mysqli->select_db(ForgeConfig::get('sys_dbname'));

        echo "Setup Pull Request REST Tests configuration \n";

        $this->enableCommentFeatureFlag();
        $this->insertPullRequest();
        $this->insertFakeGitPullRequestReferences();
        $this->insertPullRequestComments();
        $this->insertFakeInlineComments();
    }

    private function insertPullRequest(): void
    {
        $sql = <<<EOSQL
        INSERT INTO plugin_pullrequest_review (id, title, description, description_format, repository_id, user_id, creation_date, branch_src, sha1_src, repo_dest_id, branch_dest, sha1_dest, merge_status)
        VALUES (1, 'dianodal megalith', '', 'text', 1, 102, 1556314897, 'dev', 'fake_sha1_srcaaaaaaaaaaaaaaaaaaaaaaaaaaa', 1, 'master', 'fake_sha1_destaaaaaaaaaaaaaaaaaaaaaaaaaa', 0),
               (2, 'endopsychic antilogy', '', 'text', 1, 102, 1384633369, 'feature_a', 'fake_sha1_srcbbbbbbbbbbbbbbbbbbbbbbbbbbb', 1, 'master', 'fake_sha1_destbbbbbbbbbbbbbbbbbbbbbbbbbb', 0)
        EOSQL;

        $this->mysqli->real_query($sql);
    }

    private function insertFakeGitPullRequestReferences(): void
    {
        $sql = <<<EOSQL
        INSERT INTO plugin_pullrequest_git_reference (pr_id, reference_id, repository_dest_id, status)
        VALUES (1, 1, 1, 0),
               (2, 2, 1, 0)
        EOSQL;

        $this->mysqli->real_query($sql);
    }

    private function insertPullRequestComments(): void
    {
        $sql = <<<EOSQL
        INSERT INTO plugin_pullrequest_comments (id, pull_request_id, user_id, post_date, content, parent_id, color, format, last_edition_date)
        VALUES (1, 1, 102, '1387539562', 'If the Easter Bunny and the Tooth Fairy had babies would they take your teeth and leave chocolate for you?', 0, '', 'text', NULL),
               (2, 1, 102, '1455598096', 'This is the last random sentence I will be writing and I am going to stop mid-sent', 0, '', 'text', NULL),
               (3, 1, 102, '1683134450', 'I am never at home on Sundays.', 0, '', 'text', NULL),
               (4, 2, 102, '1869912034', 'I am never at home on Mondays.', 0, '', 'text', NULL)
        EOSQL;

        $this->mysqli->real_query($sql);
    }

    private function insertFakeInlineComments(): void
    {
        $sql = <<<EOSQL
        INSERT INTO plugin_pullrequest_inline_comments (id, pull_request_id, user_id, post_date, file_path, unidiff_offset, content, is_outdated, position, parent_id, color, format, last_edition_date)
        VALUES (1, 1, 102, 1617961430, 'path/to/file.php', 10, 'nonsmoking pannage', 0, 'right', 0, '', 'commonmark', NULL);
        EOSQL;

        $this->mysqli->real_query($sql);
    }

    private function enableCommentFeatureFlag(): void
    {
        $sql = sprintf(
            "INSERT INTO forgeconfig (name, value) VALUES ('feature_flag_%s', 1)",
            FeatureFlagEditComments::FEATURE_FLAG_KEY
        );
        $this->mysqli->real_query($sql);
    }
}
