<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
 * Copyright © STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2006.
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
 */

use Tuleap\Docman\View\DocmanViewURLBuilder;

class Docman_View_Admin_MetadataDetails extends Docman_View_Extra
{

    public function _title($params)
    {
        echo '<h2>' . $this->_getTitle($params) . ' - ' . sprintf(dgettext('tuleap-docman', '"%1$s" Property details'), $this->hp->purify($params['md']->getName())) . '</h2>';
    }

    public function _content($params)
    {
        $md = $params['md'];

        $sthCanChange = false;
        $mdContent = '';

        $mdContent .= '<h3>' . dgettext('tuleap-docman', 'Property parameters') . '</h3>';

        $mdContent .= '<table>';

        $metaMdHtml = new Docman_MetaMetadataHtml($md);

        $mdContent .= $metaMdHtml->getName($sthCanChange);
        $mdContent .= $metaMdHtml->getDescription($sthCanChange);
        $mdContent .= $metaMdHtml->getType($sthCanChange);
        $mdContent .= $metaMdHtml->getEmptyAllowed($sthCanChange);
        if ($md->getType() == PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
            $mdContent .= $metaMdHtml->getMultipleValuesAllowed($sthCanChange);
        }
        $mdContent .= $metaMdHtml->getUseIt($sthCanChange);
        $mdContent .= $metaMdHtml->getKeepHistory($sthCanChange);

        $mdContent .= '</table>';

        if ($sthCanChange) {
            $act_url = DocmanViewURLBuilder::buildUrl($params['default_url'], array());
            echo '<form name="md_details_update" method="POST" action="' . $act_url . '" class="docman_form">';
            echo '<input type="hidden" name="label" value="' . $md->getLabel() . '" />';
            echo '<input type="hidden" name="action" value="admin_md_details_update" />';
            echo $mdContent;
            echo '<input type="submit" name="submit" value="' . dgettext('tuleap-docman', 'Modify') . '" />';
            echo '</form>';
        } else {
            echo $mdContent;
        }

        // Display list of values
        if ($md->getType() == PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
            echo '<h3>' . dgettext('tuleap-docman', 'Property values') . '</h3>';

            echo '<div class="docman_admin_list_values">' . "\n";

            echo html_build_list_table_top(array(dgettext('tuleap-docman', 'Name'),
                                                 dgettext('tuleap-docman', 'Description'),
                                                 dgettext('tuleap-docman', 'Status'),
                                                 dgettext('tuleap-docman', 'Delete')));
            $vIter = $md->getListOfValueIterator();
            $vIter->rewind();
            $rowColorIdx = 0;
            while ($vIter->valid()) {
                $e = $vIter->current();

                // Status
                $canDelete = false;
                $displayed = true;
                switch ($e->getStatus()) {
                    case 'A':
                        $canDelete = true;
                        $status = dgettext('tuleap-docman', 'Active');
                        break;
                    case 'P':
                        $status = dgettext('tuleap-docman', 'Permanent');
                        break;
                    case 'D':
                        $displayed = false;
                        break;
                    default:
                        $status = dgettext('tuleap-docman', 'Inactive');
                }

                if ($displayed) {
                    $class = ' class="' . html_get_alt_row_color($rowColorIdx++) . '"';
                    echo '<tr' . $class . '>';

                    // Name
                    $name = Docman_MetadataHtmlList::_getElementName($e);
                    if ($e->getId() > 100) {
                        $url = DocmanViewURLBuilder::buildUrl($params['default_url'], array('action' => 'admin_display_love',
                                                                             'md' => $md->getLabel(),
                                                                             'loveid' => $e->getId()));
                        $href = '<a href="' . $url . '">' . $name . '</a>';
                    } else {
                        $href = $name;
                    }
                    echo '<td>' . $href . '</td>';

                    // Description
                    echo '<td>' . Docman_MetadataHtmlList::_getElementDescription($e) . '</td>';

                    // Status
                    echo '<td>' . $status . '</td>';

                    // Delete
                    $trash = '-';
                    if ($canDelete) {
                        $link = '?group_id=' . $params['group_id'] . '&action=admin_delete_love&loveid=' . $e->getId() . '&md=' . $md->getLabel();
                        $warn = sprintf(dgettext('tuleap-docman', 'You are about to delete the value \'%1$s\' in the current category. All documents already labeled with this value will be bound to \'None\'. Click on \'Ok\' to proceed otherwise click on \'Cancel\'.'), $name);
                        $alt  = sprintf(dgettext('tuleap-docman', 'Delete value \'%1$s\''), $name);
                        $trash = html_trash_link($link, $warn, $alt);
                    }
                    echo '<td>' . $trash . '</td>';

                    echo '</tr>';
                }
                $vIter->next();
            }
            echo '</table>';
            echo '</div><!--  docman_admin_list_values -->' . "\n";

            if ($md->getLabel() != 'status') {
                echo '<h3>' . dgettext('tuleap-docman', 'Create a new value') . '</h3>';

                $loveDetailsHtml = new Docman_View_LoveDetails($md);

                echo '<form name="md_create_love" method="POST" action="?group_id=' . $params['group_id'] . '&action=admin_create_love" class="docman_form">';
                echo $loveDetailsHtml->getHiddenFields();

                echo '<table>';

                echo $loveDetailsHtml->getNameField();
                echo $loveDetailsHtml->getDescriptionField();
                echo $loveDetailsHtml->getRankField();

                echo '</table>';

                echo '<input type="submit" name="submit" value="' . dgettext('tuleap-docman', 'Create') . '" />';

                echo '</form>';
            }
        }

        $backUrl  = DocmanViewURLBuilder::buildUrl(
            $params['default_url'],
            array('action' => 'admin_metadata')
        );
        echo '<p><a href="' . $backUrl . '">' . dgettext('tuleap-docman', 'Back to Properties menu') . '</a></p>';
    }
}
