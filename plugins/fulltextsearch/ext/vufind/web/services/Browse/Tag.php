<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once 'services/Browse/Browse.php';

require_once 'services/MyResearch/lib/Tags.php';

class Tag extends Browse {
    
    function launch()
    {
        global $interface;
        global $configArray;

        if (isset($_GET['findby'])) {
            $interface->assign('findby', $_GET['findby']);
            // Special case -- display alphabet selection if necessary:
            if ($_GET['findby'] == 'alphabetical') {
                $legalLetters = $this->getAlphabetList();
                $interface->assign('alphabetList', $legalLetters);
                // Only display tag list when a valid letter is selected:
                if (isset($_GET['letter']) && 
                    in_array($_GET['letter'], $legalLetters)) {
                    $interface->assign('startLetter', $_GET['letter']);
                    // Note -- this does not need to be escaped because 
                    // $_GET['letter'] has already been validated against
                    // the getAlphabetList() method below!
                    $clause = " AND tags.tag LIKE '{$_GET['letter']}%'";
                    $interface->assign('tagList', $this->getTagList($clause));
                }
            // Default case -- always display tag list for non-alphabetical modes:
            } else {
                $interface->assign('tagList', $this->getTagList());
            }
        }

        $interface->setPageTitle('Browse the Collection');
        $interface->setTemplate('tag.tpl');
        $interface->display('layout.tpl');
    }

    /**
     * Get a list of initial letters to display in alphabetical mode.
     *
     * @access  private
     * @return  array
     */
    private function getAlphabetList()
    {
        return array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
            'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
    }

    /**
     * Get a list of tags based on current GET parameters.
     *
     * @access  private
     * @param   string      $extra_where        Where clause to add to lookup query;
     *                                          it is caller's responsibility to
     *                                          make sure this is safe!!
     * @return  array                           Tag details.
     */
    private function getTagList($extra_where = '')
    {
        $tagList = array();
        $tag = new Tags();
        $sql = "SELECT tags.tag, COUNT(resource_tags.id) as cnt " .
            "FROM tags, resource_tags " .
            "WHERE tags.id = resource_tags.tag_id{$extra_where} GROUP BY tags.tag";
        switch ($_GET['findby']) {
            case 'alphabetical':
                $sql .= " ORDER BY tags.tag, cnt DESC";
                break;
            case 'popularity':
                $sql .= " ORDER BY cnt DESC, tags.tag";
                break;
            case 'recent':
                $sql .= " ORDER BY resource_tags.posted DESC, cnt DESC, tags.tag";
                break;
        }
        // Limit the size of our results based on the ini browse limit setting
        $browseLimit = isset($configArray['Browse']['result_limit']) ?
            $configArray['Browse']['result_limit'] : 100;
        $sql .= " LIMIT " . $browseLimit;
        $tag->query($sql);
        if ($tag->N) {
            while ($tag->fetch()) {
                $tagList[] = clone($tag);
            }
        }
        return $tagList;
    }
}

?>