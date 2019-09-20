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

/**
 * Utility class for creating and merging trees
 */
class Trees
{

    /**
     * Returns a tree of nodes build using a list of nodes: (node_id => array of children_id) (recursive)
     */
    private static function nodeListToTreeRec($listOfNodes, $nodeId)
    {
        $children = null;
        if (array_key_exists($nodeId, $listOfNodes)) {
            foreach ($listOfNodes[$nodeId] as $child) {
                $children[$child] = self::nodeListToTreeRec($listOfNodes, $child);
            }
        }

        return $children;
    }

    /**
     * Find the root of a tree in a list of nodes
     */
    private static function findRoot($listOfNodes)
    {
        foreach ($listOfNodes as $rootCandidate => $children) {
            $isRoot = true;
            foreach ($listOfNodes as $currentNode) {
                if (in_array($rootCandidate, $currentNode)) {
                    $isRoot = false;
                    break;
                }
            }
            if ($isRoot) {
                return $rootCandidate;
            }
        }
        return null;
    }

    /**
     * Returns a tree of nodes build using a list of nodes: (node_id => array of children_id)
     */
    public static function nodeListToTree($listOfNodes)
    {
        $root = self::findRoot($listOfNodes);
        if ($root === null) {
            return null;
        } else {
            return array($root => self::nodeListToTreeRec($listOfNodes, $root));
        }
    }

    /**
     * Megre two trees and tag the nodes with the information: IN_FIRST, IN_SECOND, IN_BOTH (recursive)
     */
    private static function mergeTagRec($array1, $array2)
    {
        $res = null;

        if ($array1 != null) {
            foreach ($array1 as $k => $v) {
                if ($k != 'children') {
                    $res[$k] = $v;
                }
            }
        }

        if ($array2 != null) {
            foreach ($array2 as $k => $v) {
                if ($k != 'children') {
                    $res[$k] = $v;
                }
            }
        }

        if ($array1 != null && isset($array1['children'])) {
            foreach ($array1['children'] as $name1 => $node1) {
                if (isset($array2['children']) && array_key_exists($name1, $array2['children'])) {
                    $res['children'][$name1] = self::mergeTagRec($array1['children'][$name1], $array2['children'][$name1]);
                    $res['children'][$name1]['tag'] = 'IN_BOTH';
                } else {
                    $res['children'][$name1] = $node1;
                    self::tagTree($res['children'][$name1], 'IN_FIRST');
                }
            }
        }

        if ($array2 != null && isset($array2['children'])) {
            foreach ($array2['children'] as $name2 => $node2) {
                if (!isset($res['children'][$name2])) {
                    $res['children'][$name2] = $node2;
                    self::tagTree($res['children'][$name2], 'IN_SECOND');
                }
            }
        }

        return $res;
    }

    /**
     * Set recursively the given tag to all nodes of the tree
     */
    private static function tagTree(&$tree, $tag)
    {
        $tree['tag'] = $tag;
        if (isset($tree['children'])) {
            foreach ($tree['children'] as $name => $node) {
                self::tagTree($tree['children'][$name], $tag);
            }
        }
    }

    /**
     * Merge two trees and tag the nodes with the information: IN_FIRST, IN_SECOND, IN_BOTH
     */
    public static function mergeTag(array $array1, array $array2)
    {
        $array1_keys = array_keys($array1);
        $root1 = array_pop($array1_keys);
        $array2_keys = array_keys($array2);
        $root2 = array_pop($array2_keys);

        $res['(root)'] =  self::mergeTagRec($array1[$root1], $array2[$root2]);

        return $res;
    }
}
