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
use Tuleap\Gitlab\Test\Builder\GroupLinkBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class GroupUpdatorTest extends TestCase
{
    /**
     * @var UpdateBranchPrefixOfGroup&\PHPUnit\Framework\MockObject\MockObject
     */
    private $branch_prefix_dao;
    /**
     * @var UpdateArtifactClosureOfGroup&\PHPUnit\Framework\MockObject\MockObject
     */
    private $artifact_closure_dao;
    private GitlabGroupPATCHRepresentation $patch_payload;

    protected function setUp(): void
    {
        $this->branch_prefix_dao    = $this->createMock(UpdateBranchPrefixOfGroup::class);
        $this->artifact_closure_dao = $this->createMock(UpdateArtifactClosureOfGroup::class);

        $this->patch_payload = GitlabGroupPATCHRepresentation::build('dev/', true);
    }

    private function updateGroupLink(): void
    {
        $group_link = GroupLinkBuilder::aGroupLink(1)
            ->withAllowArtifactClosure(true)
            ->withNoBranchPrefix()
            ->build();

        $updator = new GroupUpdator($this->branch_prefix_dao, $this->artifact_closure_dao);

        $updator->updateGroupLinkFromPATCHRequest($group_link, $this->patch_payload);
    }

    public function testItAsksToSaveThePrefix(): void
    {
        $this->patch_payload = GitlabGroupPATCHRepresentation::build('prefix', null);

        $this->branch_prefix_dao->expects(self::once())->method("updateBranchPrefixOfGroupLink");
        $this->updateGroupLink();
    }

    public function testItDoesNotAskToSaveThePrefixIfNoPrefixProvided(): void
    {
        $this->patch_payload = GitlabGroupPATCHRepresentation::build(null, null);

        $this->branch_prefix_dao->expects(self::never())->method("updateBranchPrefixOfGroupLink");
        $this->updateGroupLink();
    }

    public function testItThrowsAnExceptionIfPrefixIsNotValid(): void
    {
        $this->patch_payload = GitlabGroupPATCHRepresentation::build("not_valid[[[~~~prefix", null);

        $this->expectException(RestException::class);
        $this->updateGroupLink();
    }

    public function testItAsksToSaveTheArtifactClosure(): void
    {
        $this->patch_payload = GitlabGroupPATCHRepresentation::build(null, true);

        $this->artifact_closure_dao->expects(self::once())->method("updateArtifactClosureOfGroupLink");
        $this->updateGroupLink();
    }

    public function testItAsksToSaveBothPrefixAndArtifactClosure(): void
    {
        $this->branch_prefix_dao->expects(self::once())->method("updateBranchPrefixOfGroupLink");
        $this->artifact_closure_dao->expects(self::once())->method("updateArtifactClosureOfGroupLink");
        $this->updateGroupLink();
    }
}
