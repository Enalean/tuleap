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
 *
 */

declare(strict_types=1);

namespace Tuleap\Git\REST\v1\Branch;

use Git_Command_Exception;
use Luracast\Restler\RestException;
use Tuleap\Git\Branch\BranchCreationExecutor;
use Tuleap\Git\Branch\CannotCreateNewBranchException;
use Tuleap\Git\Permissions\AccessControlVerifier;
use Tuleap\Git\REST\v1\GitBranchPOSTRepresentation;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class BranchCreatorTest extends TestCase
{
    private const REPO_ID    = 1;
    private const PROJECT_ID = 101;

    private BranchCreator $creator;
    /**
     * @var \Git_Exec&\PHPUnit\Framework\MockObject\MockObject
     */
    private $git_exec;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&BranchCreationExecutor
     */
    private $branch_creation_executor;
    /**
     * @var AccessControlVerifier&\PHPUnit\Framework\MockObject\MockObject
     */
    private $access_control_verifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->git_exec                 = $this->createMock(\Git_Exec::class);
        $this->branch_creation_executor = $this->createMock(BranchCreationExecutor::class);
        $this->access_control_verifier  = $this->createMock(AccessControlVerifier::class);

        $this->creator = new BranchCreator(
            $this->git_exec,
            $this->branch_creation_executor,
            $this->access_control_verifier
        );

        $this->git_exec->method('getAllBranchesSortedByCreationDate')->willReturn([
            "main",
            "existing_branch_01",
        ]);

        $this->git_exec->method('getObjectType')->willReturnMap([
            ["main", "commit"],
            ["tag01", "tag"],
        ]);
    }

    public function testItAsksToCreateABranch(): void
    {
        $this->branch_creation_executor->expects(self::once())
            ->method("createNewBranch");

        $this->creator->createBranch(
            $this->buildMockUserWithPermissions(),
            $this->buildMockGitRepository(),
            GitBranchPOSTRepresentation::build(
                "new_branch",
                "main"
            )
        );
    }

    public function testItThrowsAnExceptionIfUserCannotWriteInRepository(): void
    {
        $this->branch_creation_executor->expects(self::never())
            ->method("createNewBranch");

        $this->expectException(RestException::class);
        $this->expectExceptionMessage("You are not allowed to create the branch new_branch in repository repo01");

        $this->creator->createBranch(
            $this->buildMockUserWithoutPermissions(),
            $this->buildMockGitRepository(),
            GitBranchPOSTRepresentation::build(
                "new_branch",
                "main"
            )
        );
    }

    public function testItThrowsAnExceptionIfBranchNameIsNotValid(): void
    {
        $this->branch_creation_executor->expects(self::never())
            ->method("createNewBranch");

        $this->expectException(RestException::class);
        $this->expectExceptionMessage("The branch name new~branch is not a valid branch name");

        $this->creator->createBranch(
            $this->buildMockUserWithPermissions(),
            $this->buildMockGitRepository(),
            GitBranchPOSTRepresentation::build(
                "new~branch",
                "main"
            )
        );
    }

    public function testItThrowsAnExceptionIfBranchWillBeCreatedFromATag(): void
    {
        $this->branch_creation_executor->expects(self::never())
            ->method("createNewBranch");

        $this->expectException(RestException::class);
        $this->expectExceptionMessage("The object tag01 is neither a branch nor a commit in repository repo01.");

        $this->creator->createBranch(
            $this->buildMockUserWithPermissions(),
            $this->buildMockGitRepository(),
            GitBranchPOSTRepresentation::build(
                "new_branch",
                "tag01"
            )
        );
    }

    public function testItThrowsAnExceptionIfBranchAlreadyExists(): void
    {
        $this->branch_creation_executor->expects(self::never())
            ->method("createNewBranch");

        $this->expectException(RestException::class);
        $this->expectExceptionMessage("The branch existing_branch_01 already exists in the repository repo01");

        $this->creator->createBranch(
            $this->buildMockUserWithPermissions(),
            $this->buildMockGitRepository(),
            GitBranchPOSTRepresentation::build(
                "existing_branch_01",
                "main"
            )
        );
    }

    public function testItThrowsAnExceptionIfReferenceDoesNotExistInRepository(): void
    {
        $this->branch_creation_executor->expects(self::never())
            ->method("createNewBranch");

        $this->git_exec->method('getObjectType')->with("0")->willThrowException(
            new Git_Command_Exception("cmd", [], 128)
        );

        $this->expectException(RestException::class);
        $this->expectExceptionMessage("The object 0 does not exist in the repository repo01");

        $this->creator->createBranch(
            $this->buildMockUserWithPermissions(),
            $this->buildMockGitRepository(),
            GitBranchPOSTRepresentation::build(
                "new_branch",
                "0"
            )
        );
    }

    public function testItThrowsAnExceptionIfGitCommandFailed(): void
    {
        $this->branch_creation_executor->expects(self::once())
            ->method("createNewBranch")
            ->willThrowException(
                new CannotCreateNewBranchException("")
            );

        $this->expectException(RestException::class);
        $this->expectExceptionMessageMatches("*An error occurred while creating the branch*");

        $this->creator->createBranch(
            $this->buildMockUserWithPermissions(),
            $this->buildMockGitRepository(),
            GitBranchPOSTRepresentation::build(
                "new_branch",
                "main"
            )
        );
    }

    private function buildMockUserWithPermissions(): \PFUser
    {
        $user = UserTestBuilder::anActiveUser()->build();
        $this->access_control_verifier->method('canWrite')->willReturn(true);

        return $user;
    }

    private function buildMockUserWithoutPermissions(): \PFUser
    {
        $user = UserTestBuilder::anActiveUser()->build();
        $this->access_control_verifier->method('canWrite')->willReturn(false);

        return $user;
    }

    private function buildMockGitRepository(): \GitRepository
    {
        $repository = $this->createMock(\GitRepository::class);
        $repository->method('getId')->willReturn(self::REPO_ID);
        $repository->method('getProjectId')->willReturn(self::PROJECT_ID);
        $repository->method('getName')->willReturn("repo01");

        return $repository;
    }
}
