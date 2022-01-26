<?php
/**
 * Copyright © Enalean, 2018-Present. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\Docman\View\DocmanViewURLBuilder;

class Docman_View_Admin_Metadata extends \Tuleap\Docman\View\Admin\AdminView
{
    public const IDENTIFIER = 'admin_metadata';

    protected function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    protected function getTitle(array $params): string
    {
        return self::getTabTitle();
    }

    public static function getTabTitle(): string
    {
        return dgettext('tuleap-docman', 'Manage Properties');
    }

    public static function getTabDescription(): string
    {
        return dgettext('tuleap-docman', 'Define the properties of your documents.');
    }

    protected function displayContent(array $params): void
    {
        $content = '';

        $content .= $this->getMetadataTable($params['mdIter'], $params['group_id'], $params['default_url']);
        $content .= $this->getNewMetadataForm($params['group_id']);
        $content .= $this->getImportForm($params['group_id']);

        echo $content;
    }

    /**
     * List the available metadata
     */
    private function getMetadataTable($mdIter, $groupId, $defaultUrl)
    {
        $hp = Codendi_HTMLPurifier::instance();

        $content = '';

        $content .= '<h3>' . dgettext('tuleap-docman', 'Available properties') . '</h3>' . "\n";

        $content .= dgettext('tuleap-docman', '<p>Define what properties are available in your document manager. Each property can be edited during document submission and updated in the document properties panel.</p><p><strong>Permissions:</strong> The same permissions are applied on a document and its properties.</p>') . "\n";

        $content    .= html_build_list_table_top([dgettext('tuleap-docman', 'Name'),
                                                    dgettext('tuleap-docman', 'Description'),
                                                    dgettext('tuleap-docman', 'Status'),
                                                    dgettext('tuleap-docman', 'Delete'),
                                                    ]);
        $altRowClass = 0;

        $mdIter->rewind();
        while ($mdIter->valid()) {
            $md = $mdIter->current();

            $canDelete = false;
            if (Docman_MetadataFactory::isRealMetadata($md->getLabel())) {
                $canDelete = true;
            }

            $trclass  = html_get_alt_row_color($altRowClass++);
            $content .= '<tr class="' . $trclass . '">';

            $nameUrl  = DocmanViewURLBuilder::buildUrl(
                $defaultUrl,
                ['action' => \Docman_View_Admin_MetadataDetails::IDENTIFIER,
                'md'     => $md->getLabel()]
            );
            $nameHref = '<a href="' . $nameUrl . '">' . $hp->purify($md->getName()) . '</a>';
            $content .= '<td>' . $nameHref . '</td>';

            $content .= '<td>' . $hp->purify($md->getDescription()) . '</td>';

            $content .= '<td>';
            if ($md->isRequired()) {
                $content .= "-";
            } else {
                if ($md->isUsed()) {
                    $content .= "Used";
                } else {
                    $content .= "Unused";
                }
            }
            $content .= '</td>';

            $trash = '-';
            if ($canDelete) {
                $link = DocmanViewURLBuilder::buildUrl(
                    $defaultUrl,
                    ['action' => 'admin_delete_metadata',
                    'md' => $md->getLabel()]
                );

                $warn  = sprintf(dgettext('tuleap-docman', 'Are you sure you want to delete the property \'%1$s\'?'), $hp->purify($md->getName()));
                $alt   = sprintf(dgettext('tuleap-docman', 'Delete the property \'%1$s\''), $hp->purify($md->getName()));
                $trash = html_trash_link($link, $warn, $alt);
            }
            $content .= '<td>' . $trash . '</td>';

            $content .= '</tr>' . "\n";

            $mdIter->next();
        }

        $content .= '</table>' . "\n";

        return $content;
    }

    /**
     * Return form to create a new metadata
     */
    private function getNewMetadataForm($groupId)
    {
        $content  = '';
        $content .= '<h3>' . dgettext('tuleap-docman', 'Create a new property') . '</h3>' . "\n";

        $content .= '<form name="admin_create_metadata" data-test="admin_create_metadata" method="post" action="?group_id=' . $groupId . '&action=admin_create_metadata" class="docman_form">';

        $content .= '<table>';

        $md = new Docman_Metadata();
        $md->setCanChangeName(true);
        $md->setCanChangeType(true);
        $md->setCanChangeDescription(true);
        $md->setCanChangeIsEmptyAllowed(true);
        $md->setCanChangeIsMultipleValuesAllowed(true);
        $md->setIsEmptyAllowed(true);
        $md->setIsMultipleValuesAllowed(false);

        $sthCanChange = '';
        $metaMdHtml   = new Docman_MetaMetadataHtml($md);
        $content     .= $metaMdHtml->getName($sthCanChange);
        $content     .= $metaMdHtml->getDescription($sthCanChange);
        $content     .= $metaMdHtml->getType($sthCanChange);
        $content     .= $metaMdHtml->getEmptyAllowed($sthCanChange);
        $content     .= $metaMdHtml->getMultipleValuesAllowed($sthCanChange);
        $content     .= $metaMdHtml->getUseIt($sthCanChange);

        $content .= '<tr>';
        $content .= '<td colspan="2">';
        $content .= '<input type="submit" value="' . dgettext('tuleap-docman', 'Create') . '" />';
        $content .= '</td>';
        $content .= '</tr>';

        $content .= '</table>';

        $content .= '</form>';

        return $content;
    }

    /**
     * Import metadata from a given project
     */
    private function getImportForm($groupId)
    {
        $GLOBALS['HTML']->includeFooterJavascriptSnippet("new ProjectAutoCompleter('plugin_docman_metadata_import_group', '" . util_get_dir_image_theme() . "', false);");
        $content  = '';
        $content .= '<h3>' . dgettext('tuleap-docman', 'Import properties from another project') . '</h3>' . "\n";
        $content .= '<p>' . dgettext('tuleap-docman', 'You can import properties from any public projects or private projects you are member of.') . '</p>' . "\n";
        $content .= '<form name="admin_import_metadata" method="post" action="?group_id=' . $groupId . '&action=' . \Docman_View_Admin_MetadataImport::IDENTIFIER . '">';
        $content .= '<input id="plugin_docman_metadata_import_group" name="plugin_docman_metadata_import_group" type="text" size="60" placeholder="';
        $content .= dgettext('tuleap-docman', 'Enter a project shortname or identifier here.');
        $content .= '" /><br />';
        $content .= '<input name="submit" type="submit" value="' . dgettext('tuleap-docman', 'Check before import') . '" />';
        $content .= '</form>';
        return $content;
    }
}
