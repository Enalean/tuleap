<?php
/**
 * Copyright © Enalean, 2011 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2007
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

class Docman_View_Admin_Obsolete extends \Tuleap\Docman\View\Admin\AdminView
{
    public const IDENTIFIER = 'admin_obsolete';

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
        return dgettext('tuleap-docman', 'Obsolete Documents');
    }

    public static function getTabDescription(): string
    {
        return dgettext('tuleap-docman', 'View and update obsolete documents.');
    }

    protected function isBurningParrotCompatiblePage(): bool
    {
        return true;
    }

    protected function includeStylesheets(): void
    {
        $theme_include_assets = new \Tuleap\Layout\IncludeAssets(
            __DIR__ . '/../../../../src/www/assets/docman/',
            '/assets/docman'
        );
        $GLOBALS['Response']->addCssAsset(new \Tuleap\Layout\CssAssetWithoutVariantDeclinaisons($theme_include_assets, 'admin-style'));
    }

    protected function displayContent(array $params): void
    {
        $html = '<div class="tlp-framed-vertically">';

        $html .= '<p class="tlp-framed-horizontally">';
        $html .= dgettext('tuleap-docman', 'This is the list of all documents obsolete today. If you click on document title you will be redirected to the document properties and you will be able to make it available again.');
        $html .= '</p>';

        $html .= $this->getTable($params);

        $html .= '</div>';

        print $html;
    }

    private function getTable($params)
    {
        $html = '';

        // Get root
        $itemFactory = new Docman_ItemFactory($params['group_id']);
        $rootItem    = $itemFactory->getRoot($params['group_id']);

        $nbItemsFound = 0;

        if ($rootItem !== null) {
            $itemIterator = $itemFactory->getItemList(
                $rootItem->getId(),
                $nbItemsFound,
                [
                    'user' => $params['user'],
                    'ignore_collapse' => true,
                    'obsolete_only' => true,
                ]
            );
        } else {
            $itemIterator = new ArrayIterator([]);
        }

        $table = '<table class="tlp-table">
            <thead>
                <tr>
                    <th class="document-icon"></th>
                    <th>' . dgettext('tuleap-docman', 'Title') . '</th>
                    <th>' . dgettext('tuleap-docman', 'Obsolete date') . '</th>
                </tr>
            </thead>
            <tbody>
        ';

        $itemIterator->rewind();
        while ($itemIterator->valid()) {
            $item = $itemIterator->current();
            $type = $itemFactory->getItemTypeForItem($item);
            if ($type != PLUGIN_DOCMAN_ITEM_TYPE_FOLDER) {
                $table .= '<tr>';

                $table      .= '<td class="document-icon">';
                $docmanIcons = new Docman_Icons('/plugins/docman/themes/default/images/ic/');
                $icon_src    = $docmanIcons->getIconForItem($item, $params);
                $table      .= '<img src="' . $icon_src . '" width="16" />';
                $table      .= '</td>';

                $table .= "<td>";
                $table .= '<span style="white-space: nowrap;">';
                $url    = DocmanViewURLBuilder::buildActionUrl(
                    $item,
                    $params,
                    ['action' => 'details', 'id' => $item->getId()],
                    false,
                    true
                );
                $table .= '<a data-help-window href="' . $url . '">';
                $table .= htmlentities($item->getTitle(), ENT_QUOTES, 'UTF-8');
                $table .= '</a>';
                $table .= '</span>';
                $table .= "</td>\n";

                // Obsolete date
                $table .= "<td>";
                $table .= format_date("Y-m-j", $item->getObsolescenceDate());
                $table .= "</td>\n";

                $table .= "</tr>\n";
            }
            $itemIterator->next();
        }

        $table .= '</tbody></table>';

        $html = $table;

        return $html;
    }
}
