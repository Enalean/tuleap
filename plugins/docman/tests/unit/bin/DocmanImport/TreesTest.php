<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 * Originally written by Clément Plantier, 2008
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use PHPUnit\Framework\TestCase;

require_once(__DIR__ . '/../../../../bin/DocmanImport/Trees.class.php');

/**
 * Unit tests for the Trees utility class
 */
//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class TreesTest extends TestCase
{

    public function testNodeListToTree(): void
    {
        $nodes = [];
        $tree = Trees::nodeListToTree($nodes);
        $this->assertNull($tree);

        $nodes = [0 => [0]];
        $tree = Trees::nodeListToTree($nodes);
        $this->assertNull($tree);

        $nodes = [0 => [1]];
        $tree = Trees::nodeListToTree($nodes);
        $this->assertEquals([0 => [1 => null]], $tree);

        //     0
        //    / \
        //   1   2
        //  /   / \
        // 3   4   5
        $nodes = [
                     0 => [1, 2],
                     1 => [3],
                     2 => [4, 5],
                 ];
        $tree = Trees::nodeListToTree($nodes);
        $this->assertEquals([0 => [1 => [3 => null], 2 => [4 => null, 5 => null]]], $tree);
    }

    public function testMergeTag(): void
    {
        $tree1 = [0 => null];
        $res = Trees::mergeTag($tree1, $tree1);
        $this->assertEquals(['(root)' => null], $res);

        $tree1 = [0 => null];
        $tree2 = [1 => null];
        $res = Trees::mergeTag($tree1, $tree2);
        $this->assertEquals(['(root)' => null], $res);

        //     0
        //    / \
        //   1   2
        //  /   / \
        // 3   4   5
        $tree1 = [
                     0 => [
                              'children' => [
                                                1 => [
                                                         'children' => [3 => null]
                                                     ],
                                                2 => [
                                                         'children' => [4 => null, 5 => null]
                                                     ],
                                                ],
                                            ],
                 ];

        $expected = [
                     '(root)' => [
                              'children' => [
                                                1 => [
                                                         'children' => [3 => ['tag' => 'IN_BOTH']],
                                                         'tag'      => 'IN_BOTH',
                                                     ],
                                                2 => [
                                                         'children' => [4 => ['tag' => 'IN_BOTH'], 5 => ['tag' => 'IN_BOTH']],
                                                         'tag'      => 'IN_BOTH',
                                                     ],
                                                ],
                                            ],
                 ];

        $res = Trees::mergeTag($tree1, $tree1);
        $this->assertEquals($expected, $res);

        // Tree 1
        //
        //     0
        //    / \
        //   1   2
        //  / \   \
        // 3   4   5
        $tree1 = [
                     0 => [
                              'children' => [
                                                1 => ['children' => [3 => null, 4 => null]],
                                                2 => ['children' => [5 => null]],
                                            ],
                              'somedata' => 1,
                          ],
                 ];

        // Tree 2
        //
        //      0
        //    / | \
        //   1  2  7
        //      |
        //      6
        $tree2 = [
                     0 => [
                              'children' => [
                                                1 => null,
                                                2 => ['children' => [6 => null]],
                                                7 => ['somedata' => 2],
                                            ],
                          ],
                 ];

        // Expected result: the two previous trees merged
        //
        //      0
        //    / | \
        //   1  7  2
        //  / \   / \
        // 3   4 5   6
        $expected = [
                     '(root)' => [
                              'children' => [
                                                1 => ['children' => [3 => ['tag' => 'IN_FIRST'], 4 => ['tag' => 'IN_FIRST']], 'tag' => 'IN_BOTH'],
                                                2 => ['children' => [5 => ['tag' => 'IN_FIRST'], 6 => ['tag' => 'IN_SECOND']], 'tag' => 'IN_BOTH'],
                                                7 => ['tag' => 'IN_SECOND', 'somedata' => 2],
                                            ],
                              'somedata' => 1,
                          ],
                 ];

         $res = Trees::mergeTag($tree1, $tree2);
         $this->assertEquals($expected, $res);
    }
}
