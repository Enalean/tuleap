<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Group;

use Luracast\Restler\RestException;
use Tuleap\Gitlab\REST\v1\Group\GitlabGroupPATCHRepresentation;
use Tuleap\Test\PHPUnit\TestCase;

final class GroupUpdatorTest extends TestCase
{
    private GroupUpdator $updator;
    /**
     * @var UpdateBranchPrefixOfGroup&\PHPUnit\Framework\MockObject\MockObject
     */
    private $branch_prefix_dao;
    /**
     * @var UpdateArtifactClosureOfGroup&\PHPUnit\Framework\MockObject\MockObject
     */
    private $artifact_closure_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch_prefix_dao    = $this->createMock(UpdateBranchPrefixOfGroup::class);
        $this->artifact_closure_dao = $this->createMock(UpdateArtifactClosureOfGroup::class);
        $this->updator              = new GroupUpdator($this->branch_prefix_dao, $this->artifact_closure_dao);
    }

    public function testItAsksToSaveThePrefix(): void
    {
        $this->branch_prefix_dao->expects(self::once())->method("updateBranchPrefixOfGroupLink");

        $this->updator->updateGroupLinkFromPATCHRequest(
            $this->buildGitlabGroup(),
            GitlabGroupPATCHRepresentation::build("prefix", null),
        );
    }

    public function testItDoesNotAskToSaveThePrefixIfNoPrefixProvided(): void
    {
        $this->branch_prefix_dao->expects(self::never())->method("updateBranchPrefixOfGroupLink");

        $this->updator->updateGroupLinkFromPATCHRequest(
            $this->buildGitlabGroup(),
            GitlabGroupPATCHRepresentation::build(null, null),
        );
    }

    public function testItThrowsAnExceptionIfPrefixIsNotValid(): void
    {
        $this->expectException(RestException::class);

        $this->updator->updateGroupLinkFromPATCHRequest(
            $this->buildGitlabGroup(),
            GitlabGroupPATCHRepresentation::build("not_valid[[[~~~prefix", null),
        );
    }

    public function testItAsksToSaveTheArtifactClosure(): void
    {
        $this->artifact_closure_dao->expects(self::once())->method("updateArtifactClosureOfGroupLink");

        $this->updator->updateGroupLinkFromPATCHRequest(
            $this->buildGitlabGroup(),
            GitlabGroupPATCHRepresentation::build(null, true),
        );
    }

    public function testItAsksToSaveBothPrefixAndArtifactClosure(): void
    {
        $this->branch_prefix_dao->expects(self::once())->method("updateBranchPrefixOfGroupLink");
        $this->artifact_closure_dao->expects(self::once())->method("updateArtifactClosureOfGroupLink");

        $this->updator->updateGroupLinkFromPATCHRequest(
            $this->buildGitlabGroup(),
            GitlabGroupPATCHRepresentation::build("dev/", true),
        );
    }

    private function buildGitlabGroup(): GitlabGroup
    {
        $row = [
            'id' => 1,
            'gitlab_group_id' => 1,
            'project_id' => 101,
            'name' => "myGroup01",
            'full_path' => "path/myGroup01",
            'web_url' => "https://example.com/path/myGroup01",
            'avatar_url' => null,
            'last_synchronization_date' => 1663660781,
            'allow_artifact_closure' => 1,
            'create_branch_prefix' => "",
        ];

        return GitlabGroup::buildGitlabGroupFromRow($row);
    }
}
