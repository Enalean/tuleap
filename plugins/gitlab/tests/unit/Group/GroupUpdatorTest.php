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

use Tuleap\Gitlab\Test\Builder\GroupLinkBuilder;
use Tuleap\Gitlab\Test\Stubs\UpdateArtifactClosureOfGroupStub;
use Tuleap\Gitlab\Test\Stubs\UpdateBranchPrefixOfGroupStub;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class GroupUpdatorTest extends TestCase
{
    private UpdateBranchPrefixOfGroupStub $branch_prefix_updater;
    private UpdateArtifactClosureOfGroupStub $artifact_closure_updater;
    private ?string $branch_prefix;
    private ?bool $allow_artifact_closure;

    protected function setUp(): void
    {
        $this->branch_prefix_updater    = UpdateBranchPrefixOfGroupStub::withCallCount();
        $this->artifact_closure_updater = UpdateArtifactClosureOfGroupStub::withCallCount();

        $this->branch_prefix          = 'dev/';
        $this->allow_artifact_closure = true;
    }

    private function updateGroupLink(): Ok|Err
    {
        $group_link_id  = 1;
        $update_command = new UpdateGroupLinkCommand(
            $group_link_id,
            $this->branch_prefix,
            $this->allow_artifact_closure,
            UserTestBuilder::buildWithDefaults()
        );

        $group_link = GroupLinkBuilder::aGroupLink($group_link_id)
            ->withAllowArtifactClosure(true)
            ->withNoBranchPrefix()
            ->build();

        $updator = new GroupUpdator($this->branch_prefix_updater, $this->artifact_closure_updater);
        return $updator->updateGroupLink($group_link, $update_command);
    }

    public function testItAsksToSaveThePrefix(): void
    {
        $this->branch_prefix          = 'prefix';
        $this->allow_artifact_closure = null;

        $result = $this->updateGroupLink();
        self::assertTrue(Result::isOk($result));
        self::assertSame(1, $this->branch_prefix_updater->getCallCount());
    }

    public function testItDoesNotAskToSaveThePrefixIfNoPrefixProvided(): void
    {
        $this->branch_prefix          = null;
        $this->allow_artifact_closure = null;

        $result = $this->updateGroupLink();
        self::assertTrue(Result::isOk($result));
        self::assertSame(0, $this->branch_prefix_updater->getCallCount());
    }

    public function testItReturnsAFaultIfPrefixIsNotValid(): void
    {
        $this->branch_prefix          = "not_valid[[[~~~prefix";
        $this->allow_artifact_closure = null;

        $result = $this->updateGroupLink();
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(InvalidBranchPrefixFault::class, $result->error);
    }

    public function testItAsksToSaveTheArtifactClosure(): void
    {
        $this->branch_prefix          = null;
        $this->allow_artifact_closure = true;

        $result = $this->updateGroupLink();
        self::assertTrue(Result::isOk($result));
        self::assertSame(1, $this->artifact_closure_updater->getCallCount());
    }

    public function testItAsksToSaveBothPrefixAndArtifactClosure(): void
    {
        $result = $this->updateGroupLink();
        self::assertTrue(Result::isOk($result));
        self::assertSame(1, $this->branch_prefix_updater->getCallCount());
        self::assertSame(1, $this->artifact_closure_updater->getCallCount());
    }
}
