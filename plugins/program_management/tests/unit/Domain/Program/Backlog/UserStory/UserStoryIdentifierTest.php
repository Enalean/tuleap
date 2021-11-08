<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\SearchChildrenOfFeature;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\FeatureIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\SearchChildrenOfFeatureStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\Test\PHPUnit\TestCase;

final class UserStoryIdentifierTest extends TestCase
{
    private const USER_STORY_ID = 666;
    private SearchChildrenOfFeature $user_story_searcher;
    private VerifyIsVisibleArtifact $verify_is_visible;
    private FeatureIdentifier $feature_identifier;
    private UserIdentifier $user_identifier;

    private array $visible_user_story;
    private array $invisible_user_story;

    protected function setUp(): void
    {
        $this->visible_user_story   = ['children_id' => self::USER_STORY_ID];
        $this->invisible_user_story = ['children_id' => 404,];

        $this->verify_is_visible = VerifyIsVisibleArtifactStub::withVisibleIds(self::USER_STORY_ID);

        $this->feature_identifier = FeatureIdentifierBuilder::build(1, 101);
        $this->user_identifier    = UserIdentifierStub::buildGenericUser();
    }

    public function testSkipsIfUserCanNotSee(): void
    {
        $this->user_story_searcher = SearchChildrenOfFeatureStub::withChildren(
            [$this->invisible_user_story]
        );
        self::assertCount(
            0,
            UserStoryIdentifier::buildCollectionFromFeature(
                $this->user_story_searcher,
                $this->verify_is_visible,
                $this->feature_identifier,
                $this->user_identifier
            )
        );
    }

    public function testItBuildsUserStoryId(): void
    {
        $this->user_story_searcher = SearchChildrenOfFeatureStub::withChildren([$this->visible_user_story]);

        self::assertSame(
            self::USER_STORY_ID,
            UserStoryIdentifier::buildCollectionFromFeature(
                $this->user_story_searcher,
                $this->verify_is_visible,
                $this->feature_identifier,
                $this->user_identifier
            )[0]->getId()
        );
    }
}
