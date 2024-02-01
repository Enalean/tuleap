<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Reference\Presenters;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\GlobalLanguageMock;
use Tuleap\Reference\CrossReference;
use Tuleap\Test\PHPUnit\TestCase;

final class CrossReferenceLinkPresenterCollectionBuilderTest extends TestCase
{
    use GlobalLanguageMock;

    private CrossReferenceLinkPresenterCollectionBuilder $builder;
    private CrossReference&MockObject $cross_ref_target_1;
    private CrossReference&MockObject $cross_ref_target_2;

    protected function setUp(): void
    {
        $this->cross_ref_target_1 = $this->mockCrossReference(
            'git_commit',
            1,
            "/plugins/git/1",
            "git",
            'tracker',
            789,
            '/plugins/tracker/789',
            'tracker'
        );

        $this->cross_ref_target_2 = $this->mockCrossReference(
            'tracker',
            58,
            "/plugins/tracker/58",
            'tracker',
            'git_commit',
            1,
            '/plugins/git/1',
            'git'
        );

        $this->builder = new CrossReferenceLinkPresenterCollectionBuilder();

        $GLOBALS['HTML'] = $this->createMock(\Layout::class);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['HTML']);
    }

    public function testItReturnsEmptyArrayWhenKeyDoesNotExistInArray(): void
    {
        $array_presenter = $this->builder->build([], 'source', true);
        self::assertEquals([], $array_presenter);
    }

    public function testItReturnsOneTargetCrossRefAndDontDisplayCommaAndDisplayParams(): void
    {
        $GLOBALS['Language']->method('getText')->withConsecutive(
            ['cross_ref_fact_include', 'confirm_delete'],
            ['cross_ref_fact_include', 'delete'],
        )->willReturn("Delete the item?");
        $GLOBALS['HTML']->method('getImage');

        $cross_ref_link_1 = new CrossReferenceLinkPresenter(
            "git_commit_1",
            "git_commit #1",
            "/plugins/git/1",
            "?target_id=1&target_gid=101&target_type=git&target_key=git_commit&source_id=789&source_gid=101&source_type=tracker&source_key=tracker",
            false
        );

        $array_presenter = $this->builder->build([$this->cross_ref_target_1], 'target', true);

        self::assertEquals([$cross_ref_link_1], $array_presenter);
    }

    public function testItReturnsTwoTargetsCrossRefsAndDisplayCommaAndDisplayParams(): void
    {
        $GLOBALS['Language']->method('getText')->withConsecutive(
            ['cross_ref_fact_include', 'confirm_delete'],
            ['cross_ref_fact_include', 'delete'],
        )->willReturn("Delete the item?");
        $GLOBALS['HTML']->method('getImage');

        $cross_ref_link_1 = new CrossReferenceLinkPresenter(
            "git_commit_1",
            "git_commit #1",
            "/plugins/git/1",
            "?target_id=1&target_gid=101&target_type=git&target_key=git_commit&source_id=789&source_gid=101&source_type=tracker&source_key=tracker",
            true
        );

        $cross_ref_link_2 = new CrossReferenceLinkPresenter(
            "tracker_58",
            "tracker #58",
            "/plugins/tracker/58",
            "?target_id=58&target_gid=101&target_type=tracker&target_key=tracker&source_id=1&source_gid=101&source_type=git&source_key=git_commit",
            false
        );

        $array_presenter = $this->builder->build([$this->cross_ref_target_1, $this->cross_ref_target_2], 'target', true);

        self::assertEquals([$cross_ref_link_1, $cross_ref_link_2], $array_presenter);
    }

    public function testItReturnsOneSourceCrossRefAndDontDisplayCommaAndDontDisplayParams(): void
    {
        $GLOBALS['Language']->method('getText')->withConsecutive(
            ['cross_ref_fact_include', 'confirm_delete'],
            ['cross_ref_fact_include', 'delete'],
        )->willReturn("Delete the item?");
        $GLOBALS['HTML']->method('getImage');

        $cross_ref_link_1 = new CrossReferenceLinkPresenter(
            'tracker_789',
            'tracker #789',
            '/plugins/tracker/789',
            null,
            false,
        );

        $array_presenter = $this->builder->build([$this->cross_ref_target_1], 'source', false);

        self::assertEquals([$cross_ref_link_1], $array_presenter);
    }

    private function mockCrossReference(
        string $ref_target_key,
        int $ref_target_id,
        string $ref_target_url,
        string $ref_target_type,
        string $ref_source_key,
        int $ref_source_id,
        string $ref_source_url,
        string $ref_source_type,
    ): CrossReference&MockObject {
        $cross_ref = $this->createMock(CrossReference::class);
        $cross_ref->method('getRefTargetKey')->willReturn($ref_target_key);
        $cross_ref->method('getRefTargetId')->willReturn($ref_target_id);
        $cross_ref->method('getRefTargetGid')->willReturn(101);
        $cross_ref->method('getRefTargetUrl')->willReturn($ref_target_url);
        $cross_ref->method('getRefTargetType')->willReturn($ref_target_type);
        $cross_ref->method('getRefSourceKey')->willReturn($ref_source_key);
        $cross_ref->method('getRefSourceId')->willReturn($ref_source_id);
        $cross_ref->method('getRefSourceGid')->willReturn(101);
        $cross_ref->method('getRefSourceUrl')->willReturn($ref_source_url);
        $cross_ref->method('getRefSourceType')->willReturn($ref_source_type);

        return $cross_ref;
    }
}
