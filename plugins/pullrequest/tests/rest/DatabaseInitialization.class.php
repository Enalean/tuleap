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

namespace Tuleap\PullRequest\REST;

require_once __DIR__ . '/../../../../tests/lib/DatabaseInitialisation.class.php';

use ForgeConfig;

class DatabaseInitialization extends \DatabaseInitialization
{
    public function setUp()
    {
        $this->mysqli->select_db(ForgeConfig::get('sys_dbname'));
        $this->enableCommentFeatureFlag();
        $this->insertPullRequest();
        $this->insertFakeGitPullRequestReferences();
        $this->insertPullRequestComments();
    }

    private function insertPullRequest()
    {
        echo "Create PullRequest \n";

        $sql = "INSERT INTO plugin_pullrequest_review (id, repository_id, user_id, creation_date, branch_src, sha1_src, repo_dest_id, branch_dest, sha1_dest)
                VALUES (1, 1, 102, UNIX_TIMESTAMP(), 'dev', 'fake_sha1_srcaaaaaaaaaaaaaaaaaaaaaaaaaaa', 1, 'master', 'fake_sha1_destaaaaaaaaaaaaaaaaaaaaaaaaaa'),
                       (2, 1, 102, UNIX_TIMESTAMP(), 'feature_a', 'fake_sha1_srcbbbbbbbbbbbbbbbbbbbbbbbbbbb', 1, 'master', 'fake_sha1_destbbbbbbbbbbbbbbbbbbbbbbbbbb')";

        $this->mysqli->real_query($sql);
    }

    private function insertFakeGitPullRequestReferences()
    {
        echo "Create Git PullRequest reference \n";

        $sql = 'INSERT INTO plugin_pullrequest_git_reference (pr_id, reference_id, repository_dest_id, status)
                VALUES (1, 1, 1, 0),
                       (2, 2, 1, 0)';

        $this->mysqli->real_query($sql);
    }

    private function insertPullRequestComments()
    {
        echo "Create PullRequest Comments \n";

        $sql = 'INSERT INTO plugin_pullrequest_comments (pull_request_id, user_id, content)
                VALUES (1, 102, "If the Easter Bunny and the Tooth Fairy had babies would they take your teeth and leave chocolate for you?"),
                       (1, 102, "This is the last random sentence I will be writing and I am going to stop mid-sent"),
                       (1, 102, "I am never at home on Sundays."),
                       (2, 102, "I am never at home on Mondays.")';

        $this->mysqli->real_query($sql);
    }

    private function enableCommentFeatureFlag(): void
    {
        $sql = 'INSERT INTO forgeconfig (name, value) VALUES ("feature_flag_allow_pull_requests_comments_edition", 1)';
        $this->mysqli->real_query($sql);
    }
}
