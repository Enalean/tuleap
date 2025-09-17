<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
 * Copyright STMicroelectronics, 2009. All Rights Reserved.
 *
 * Originally written by Sabri LABBENE 2009.
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

class Docman_View_Admin_LockInfos extends \Tuleap\Docman\View\Admin\AdminView //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public const string IDENTIFIER = 'admin_lock_infos';
    public $defaultUrl;

    #[\Override]
    protected function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    #[\Override]
    protected function getTitle(array $params): string
    {
        return self::getTabTitle();
    }

    public static function getTabTitle(): string
    {
        return dgettext('tuleap-docman', 'Locked Documents');
    }

    public static function getTabDescription(): string
    {
        return dgettext('tuleap-docman', 'List of locked documents.');
    }

    #[\Override]
    protected function displayContent(\TemplateRenderer $renderer, array $params): void
    {
        $html = '<div class="tlp-framed">';

        $html .= '<section class="tlp-pane">
            <div class="tlp-pane-container">
                <div class="tlp-pane-header">
                    <h1 class="tlp-pane-title">' . dgettext('tuleap-docman', 'Locked Documents') . '</h1>
                </div>
                <section class="tlp-pane-section">';

        $html .= '<p>';
        $html .= dgettext('tuleap-docman', 'This is the list of all locked documents in project.');
        $html .= '</p>';

        $html .= $this->getTable($params);

        $html .= '</section>
            </div>
        </section>';

        print($html);
    }

    private function getTable($params)
    {
        $this->defaultUrl = $params['default_url'];
        $content          = '';

        $content .= '<table class="tlp-table">
            <thead>
                <tr>
                    <th>' . dgettext('tuleap-docman', 'Title') . '</th>
                    <th>' . dgettext('tuleap-docman', 'Location') . '</th>
                    <th>' . dgettext('tuleap-docman', 'Who') . '</th>
                    <th>' . dgettext('tuleap-docman', 'When') . '</th>
                </tr>
            </thead>
            <tbody>
        ';

        // Get list of all locked documents in the project.
        $dPM       = Docman_PermissionsManager::instance($params['group_id']);
        $lockInfos = $dPM->getLockFactory()->getProjectLockInfos($params['group_id']);

        $uH = UserHelper::instance();
        $hp = Codendi_HTMLPurifier::instance();

        $dIF = new Docman_ItemFactory($params['group_id']);

        if ($lockInfos && count($lockInfos) === 0) {
            $content .= '
                <tr>
                    <td colspan="4" class="tlp-table-cell-empty">
                        ' . dgettext('tuleap-docman', 'Project has no locked documents') . '
                    </td>
                </tr>
            ';
        }

        if ($lockInfos !== false) {
            $project = ProjectManager::instance()->getProject((int) $params['group_id']);
            $service = $project->getService(DocmanPlugin::SERVICE_SHORTNAME);
            assert($service instanceof \Tuleap\Docman\ServiceDocman);
            foreach ($lockInfos as $row) {
                $item = $dIF->getItemFromDb($row['item_id']);
                if ($item === null) {
                    return '</tbody></table>';
                }
                $parent   = $dIF->getItemFromDb($item->getParentId());
                $content .= '<tr>';
                $item_url = $service->getUrl() . 'preview/' . urlencode((string) $item->getId());
                $content .= '<td>' . '<a href="' . $item_url . '">';
                $content .= $hp->purify($item->getTitle());
                $content .= '</a></td>';
                $content .= '<td>';
                if ($parent === null || $dIF->isRoot($parent)) {
                    $content .= '</td>';
                } else {
                    $parent_url = $service->getUrl() . 'folder/' . urlencode((string) $parent->getId());
                    $content   .=  '<a href="' . $parent_url . '">';
                    $content   .= $hp->purify($parent->getTitle());
                    $content   .= '</a></td>';
                }
                $content .= '<td>' . $hp->purify($uH->getDisplayNameFromUserId($row['user_id'])) . '</td>';
                $content .= '<td>' . format_date($GLOBALS['Language']->getText('system', 'datefmt'), $row['lock_date']) . '</td>';
                $content .= '</tr>';
            }
        }

        $content .= '</tbody></table>';

        return $content;
    }
}
