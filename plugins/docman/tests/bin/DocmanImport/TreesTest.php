<?php
/**
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

require_once(__DIR__.'/../../../bin/DocmanImport/Trees.class.php');

/**
 * Unit tests for the Trees utility class
 */
class TreesTest extends TuleapTestCase
{

    public function testNodeListToTree()
    {
        $nodes = array();
        $tree = Trees::nodeListToTree($nodes);
        $this->assertNull($tree);

        $nodes = array(0 => array(0));
        $tree = Trees::nodeListToTree($nodes);
        $this->assertNull($tree);

        $nodes = array(0 => array(1));
        $tree = Trees::nodeListToTree($nodes);
        $this->assertEqual(array(0 => array(1 => null)), $tree);

        //     0
        //    / \
        //   1   2
        //  /   / \
        // 3   4   5
        $nodes = array(
                     0 => array(1, 2),
                     1 => array(3),
                     2 => array(4, 5),
                 );
        $tree = Trees::nodeListToTree($nodes);
        $this->assertEqual(array(0 => array(1 => array(3 => null), 2 => array(4 => null, 5 => null))), $tree);
    }

    public function testMergeTag()
    {
        $tree1 = array(0 => null);
        $res = Trees::mergeTag($tree1, $tree1);
        $this->assertEqual(array('(root)' => null), $res);

        $tree1 = array(0 => null);
        $tree2 = array(1 => null);
        $res = Trees::mergeTag($tree1, $tree2);
        $this->assertEqual(array('(root)' => null), $res);

        //     0
        //    / \
        //   1   2
        //  /   / \
        // 3   4   5
        $tree1 = array(
                     0 => array(
                              'children' => array(
                                                1 => array(
                                                         'children' => array(3 => null)
                                                     ),
                                                2 => array(
                                                         'children' => array(4 => null, 5 => null)
                                                     ),
                                                ),
                                            ),
                 );

        $expected = array(
                     '(root)' => array(
                              'children' => array(
                                                1 => array(
                                                         'children' => array(3 => array('tag' => 'IN_BOTH')),
                                                         'tag'      => 'IN_BOTH',
                                                     ),
                                                2 => array(
                                                         'children' => array(4 => array('tag' => 'IN_BOTH'), 5 => array('tag' => 'IN_BOTH')),
                                                         'tag'      => 'IN_BOTH',
                                                     ),
                                                ),
                                            ),
                 );

        $res = Trees::mergeTag($tree1, $tree1);
        $this->assertEqual($expected, $res);

        // Tree 1
        //
        //     0
        //    / \
        //   1   2
        //  / \   \
        // 3   4   5
        $tree1 = array(
                     0 => array(
                              'children' => array(
                                                1 => array('children' => array(3 => null, 4 => null)),
                                                2 => array('children' => array(5 => null)),
                                            ),
                              'somedata' => 1,
                          ),
                 );

        // Tree 2
        //
        //      0
        //    / | \
        //   1  2  7
        //      |
        //      6
        $tree2 = array(
                     0 => array(
                              'children' => array(
                                                1 => null,
                                                2 => array('children' => array(6 => null)),
                                                7 => array('somedata' => 2),
                                            ),
                          ),
                 );

        // Expected result: the two previous trees merged
        //
        //      0
        //    / | \
        //   1  7  2
        //  / \   / \
        // 3   4 5   6
        $expected = array(
                     '(root)' => array(
                              'children' => array(
                                                1 => array('children' => array(3 => array('tag' => 'IN_FIRST'), 4 => array('tag' => 'IN_FIRST')), 'tag' => 'IN_BOTH'),
                                                2 => array('children' => array(5 => array('tag' => 'IN_FIRST'), 6 => array('tag' => 'IN_SECOND')), 'tag' => 'IN_BOTH'),
                                                7 => array('tag' => 'IN_SECOND', 'somedata' => 2),
                                            ),
                              'somedata' => 1,
                          ),
                 );

         $res = Trees::mergeTag($tree1, $tree2);
         $this->assertEqual($expected, $res);
    }
}
