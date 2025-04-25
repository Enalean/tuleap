<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1\Workflow\PostAction\Update;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\REST\I18NRestException;
use Tuleap\Tracker\Workflow\PostAction\Update\CIBuildValue;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollection;
use Workflow;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PostActionCollectionJsonParserTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private PostActionCollectionJsonParser $collection_parser;

    private PostActionUpdateJsonParser&MockObject $action_parser;

    #[\PHPUnit\Framework\Attributes\Before]
    public function createParser(): void
    {
        $this->action_parser     = $this->createMock(PostActionUpdateJsonParser::class);
        $this->collection_parser = new PostActionCollectionJsonParser($this->action_parser);
    }

    public function testParseReturnsResultOfParserWhichAcceptsJson(): void
    {
        $another_parser    = $this->createMock(PostActionUpdateJsonParser::class);
        $collection_parser = new PostActionCollectionJsonParser($another_parser, $this->action_parser);

        $another_parser
            ->method('accept')
            ->willReturn(false);
        $this->action_parser
            ->method('accept')
            ->willReturn(true);
        $ci_build = new CIBuildValue('http://example.test');
        $this->action_parser
            ->method('parse')
            ->willReturn($ci_build);

        $workflow = $this->createMock(Workflow::class);

        $action_collection = $collection_parser->parse($workflow, [['type' => 'should match one parser']]);

        $this->assertEquals(new PostActionCollection($ci_build), $action_collection);
    }

    public function testParseWithEmptyArrayReturnsEmptyPostActionCollection(): void
    {
        $workflow = $this->createMock(Workflow::class);

        $post_actions = $this->collection_parser->parse($workflow, []);
        $this->assertEquals(new PostActionCollection(), $post_actions);
    }

    public function testParseThrowsWhenNoParserAcceptGivenJson(): void
    {
        $workflow = $this->createMock(Workflow::class);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->action_parser
            ->method('accept')
            ->willReturn(false);

        $this->collection_parser->parse($workflow, [['id' => 1]]);
    }

    public function testParseWithNotAssociativeArrayThrows(): void
    {
        $workflow = $this->createMock(Workflow::class);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->collection_parser->parse(
            $workflow,
            [
                'not an array',
            ]
        );
    }
}
