<?php
/**
 * Copyright (c) Enalean, 2026-Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Reference;

use Override;
use ParagonIE\EasyDB\EasyDB;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tuleap\DB\DBFactory;
use Tuleap\Gitlab\Reference\Branch\GitlabBranchReference;
use Tuleap\Gitlab\Reference\Commit\GitlabCommitReference;
use Tuleap\Gitlab\Reference\MergeRequest\GitlabMergeRequestReference;
use Tuleap\Gitlab\Reference\Tag\GitlabTagReference;
use Tuleap\Gitlab\Repository\Webhook\PostMergeRequest\MergeRequestTuleapReferenceDao;
use Tuleap\Gitlab\Repository\Webhook\PostPush\Branch\BranchInfoDao;
use Tuleap\Gitlab\Repository\Webhook\PostPush\Commits\CommitTuleapReferenceDao;
use Tuleap\Gitlab\Repository\Webhook\TagPush\TagInfoDao;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use Tuleap\Tracker\Artifact\Artifact;

#[DisableReturnValueGenerationForTestDoubles]
final class CrossReferenceDAOTest extends TestIntegrationTestCase
{
    private const int    INTEGRATION_ID      = 123;
    private const string BRANCH_NAME         = 'my_branch';
    private const string COMMIT_SHA1         = '0a12256aabafe1cb3728afef28114f62099fd629';
    private const int    MERGE_REQUEST_ID    = 12;
    private const string TAG_NAME            = '1.0.0-rc2';
    private const string REPOSITORY_OLD_NAME = 'fred/some_repo';
    private const string REPOSITORY_NEW_NAME = 'bob/some_group/new_repo';

    private EasyDB $db;
    private CrossReferenceDAO $dao;

    #[Override]
    protected function setUp(): void
    {
        $this->db  = DBFactory::getMainTuleapDBConnection()->getDB();
        $this->dao = new CrossReferenceDAO();

        $branch_dao        = new BranchInfoDao();
        $commit_dao        = new CommitTuleapReferenceDao();
        $merge_request_dao = new MergeRequestTuleapReferenceDao();
        $tag_dao           = new TagInfoDao();

        $branch_dao->saveGitlabBranchInfo(
            self::INTEGRATION_ID,
            self::COMMIT_SHA1,
            self::BRANCH_NAME,
            time(),
        );
        $commit_dao->saveGitlabCommitInfo(
            self::INTEGRATION_ID,
            self::COMMIT_SHA1,
            time(),
            'feat: Some feature',
            self::BRANCH_NAME,
            'fred',
            'fred@example.com',
        );
        $merge_request_dao->saveGitlabMergeRequestInfo(
            self::INTEGRATION_ID,
            self::MERGE_REQUEST_ID,
            'My MR',
            '',
            'dev',
            'open',
            time(),
        );
        $tag_dao->saveGitlabTagInfo(
            self::INTEGRATION_ID,
            self::COMMIT_SHA1,
            self::TAG_NAME,
            '',
        );

        $this->db->insertMany(
            'cross_references',
            [
                [
                    'source_type'    => GitlabBranchReference::NATURE_NAME,
                    'source_keyword' => GitlabBranchReference::REFERENCE_NAME,
                    'source_id'      => self::REPOSITORY_OLD_NAME . '/' . self::BRANCH_NAME,
                    'target_type'    => Artifact::REFERENCE_NATURE,
                    'target_keyword' => 'art',
                ],
                [
                    'source_type'    => GitlabCommitReference::NATURE_NAME,
                    'source_keyword' => GitlabCommitReference::REFERENCE_NAME,
                    'source_id'      => self::REPOSITORY_OLD_NAME . '/' . self::COMMIT_SHA1,
                    'target_type'    => Artifact::REFERENCE_NATURE,
                    'target_keyword' => 'art',
                ],
                [
                    'source_type'    => GitlabMergeRequestReference::NATURE_NAME,
                    'source_keyword' => GitlabMergeRequestReference::REFERENCE_NAME,
                    'source_id'      => self::REPOSITORY_OLD_NAME . '/' . self::MERGE_REQUEST_ID,
                    'target_type'    => Artifact::REFERENCE_NATURE,
                    'target_keyword' => 'art',
                ],
                [
                    'source_type'    => GitlabTagReference::NATURE_NAME,
                    'source_keyword' => GitlabTagReference::REFERENCE_NAME,
                    'source_id'      => self::REPOSITORY_OLD_NAME . '/' . self::TAG_NAME,
                    'target_type'    => Artifact::REFERENCE_NATURE,
                    'target_keyword' => 'art',
                ],
                [
                    'source_type'    => Artifact::REFERENCE_NATURE,
                    'source_keyword' => 'art',
                    'source_id'      => '458',
                    'target_type'    => Artifact::REFERENCE_NATURE,
                    'target_keyword' => 'art',
                ],
            ],
        );
    }

    public function testItUpdatesBranchsCrossReferences(): void
    {
        $this->dao->updateBranchCrossReference(self::INTEGRATION_ID, self::REPOSITORY_OLD_NAME, self::REPOSITORY_NEW_NAME);

        $cross_reference = $this->db->row('SELECT * FROM cross_references WHERE source_type = ?', GitlabBranchReference::NATURE_NAME);
        self::assertSame(self::REPOSITORY_NEW_NAME . '/' . self::BRANCH_NAME, $cross_reference['source_id']);
    }

    public function testItUpdatesCommitCrossReferences(): void
    {
        $this->dao->updateCommitCrossReference(self::INTEGRATION_ID, self::REPOSITORY_OLD_NAME, self::REPOSITORY_NEW_NAME);

        $cross_reference = $this->db->row('SELECT * FROM cross_references WHERE source_type = ?', GitlabCommitReference::NATURE_NAME);
        self::assertSame(self::REPOSITORY_NEW_NAME . '/' . self::COMMIT_SHA1, $cross_reference['source_id']);
    }

    public function testItUpdatesMergeRequestCrossReferences(): void
    {
        $this->dao->updateMergeRequestCrossReference(self::INTEGRATION_ID, self::REPOSITORY_OLD_NAME, self::REPOSITORY_NEW_NAME);

        $cross_reference = $this->db->row('SELECT * FROM cross_references WHERE source_type = ?', GitlabMergeRequestReference::NATURE_NAME);
        self::assertSame(self::REPOSITORY_NEW_NAME . '/' . self::MERGE_REQUEST_ID, $cross_reference['source_id']);
    }

    public function testItUpdatesTagRequestCrossReferences(): void
    {
        $this->dao->updateTagCrossReference(self::INTEGRATION_ID, self::REPOSITORY_OLD_NAME, self::REPOSITORY_NEW_NAME);

        $cross_reference = $this->db->row('SELECT * FROM cross_references WHERE source_type = ?', GitlabTagReference::NATURE_NAME);
        self::assertSame(self::REPOSITORY_NEW_NAME . '/' . self::TAG_NAME, $cross_reference['source_id']);
    }
}
