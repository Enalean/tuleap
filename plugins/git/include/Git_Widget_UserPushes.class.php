<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2012. All Rights Reserved.
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

use Tuleap\date\RelativeDatesAssetsRetriever;
use Tuleap\Layout\CssAssetCollection;
use Tuleap\Layout\CssViteAsset;
use Tuleap\Layout\IncludeViteAssets;

/**
 * Widget displaying last git pushes for the user
 */
class Git_Widget_UserPushes extends Widget
{
    public $offset   = 5;
    public $pastDays = 30;
    public $pluginPath;

    /**
     * Constructor of the class
     *
     * @param String $pluginPath Path of plugin git
     *
     * @return Void
     */
    public function __construct($pluginPath)
    {
        $this->pluginPath = $pluginPath;
        parent::__construct('plugin_git_user_pushes');
        $this->offset = user_get_preference('plugin_git_user_pushes_offset');
        if (empty($this->offset)) {
            $this->offset = 5;
        }
        $this->pastDays = user_get_preference('plugin_git_user_pushes_past_days');
        if (empty($this->pastDays)) {
            $this->pastDays = 30;
        }
    }

    /**
     * Get the title of the widget.
     *
     * @return String
     */
    public function getTitle()
    {
        return dgettext('tuleap-git', 'My last Git pushes');
    }

    /**
     * Compute the content of the widget
     *
     * @return String
     */
    public function getContent()
    {
        $dao     = new Git_LogDao();
        $um      = UserManager::instance();
        $user    = $um->getCurrentUser();
        $date    = $_SERVER['REQUEST_TIME'] - ($this->pastDays * 24 * 60 * 60);
        $result  = $dao->getLastPushesRepositories($user->getId(), $date);
        $content = '';
        $project = '';
        $hp      = Codendi_HTMLPurifier::instance();
        if (count($result) > 0) {
            foreach ($result as $entry) {
                if (! empty($entry['repository_namespace'])) {
                    $namespace = $entry['repository_namespace'] . "/";
                } else {
                    $namespace = '';
                }
                $rows = $dao->getLastPushesByUser($user->getId(), $entry['repository_id'], $this->offset, $date);
                if (count($rows) > 0) {
                    if ($project != $entry['group_name']) {
                        if (! empty($project)) {
                            $content .= '</fieldset>';
                        }
                        $project   = $entry['group_name'];
                        $unix_name = $hp->purify($entry['unix_group_name']);
                        $content  .= '<fieldset class="widget-last-git-pushes-project">
                            <legend id="plugin_git_user_pushes_widget_project_' . $unix_name . '" class="' . Toggler::getClassname('plugin_git_user_pushes_widget_project_' . $unix_name) . '">
                            <span title="' . dgettext('tuleap-git', 'Project') . '">
                            <b>' . $hp->purify($project) . '</b>
                            </span>
                            </legend>
                            <div class="widget-last-git-pushes-details">
                            <a href="' . $this->pluginPath . '/index.php?group_id=' . $entry['group_id'] . '">
                                [ ' . dgettext('tuleap-git', 'Details') . ' ]
                            </a>
                            </div>';
                    }
                    $content .= '<fieldset class="widget-last-git-pushes-repository">
                        <legend
                            id="plugin_git_user_pushes_widget_repo_' . $project . $namespace . $entry['repository_name'] . '"
                            class="' . Toggler::getClassname('plugin_git_user_pushes_widget_repo_' . $project . $namespace . $entry['repository_name']) . '"
                        >
                        <span title="' . dgettext('tuleap-git', 'Repository') . '">
                        ' . $namespace . $entry['repository_name'] . '
                        </span>
                        </legend>
                        <table class="tlp-table">
                        <thead>
                        <tr>
                        <th>' . dgettext('tuleap-git', 'Date') . '</th>
                        <th>' . dgettext('tuleap-git', 'Commits') . '</th>
                        </tr>
                        </thead>
                        <tbody>';
                    $i        = 0;
                    foreach ($rows as $row) {
                        $content .= '<tr class="' . html_get_alt_row_color(++$i) . '">
                                         <td>' . DateHelper::relativeDateInlineContext((int) $row['push_date'], $user) . '</td>
                                         <td>
                                             <a href="' . $this->pluginPath . '/index.php/' . $entry['group_id'] . '/view/' . $entry['repository_id'] . '/">
                                             ' . $hp->purify($row['commits_number']) . '
                                             </a>
                                         </td>
                                     </tr>';
                    }
                    $content .= "</tbody></table>
                                 </fieldset>";
                } else {
                    $content .= '<p>' . dgettext('tuleap-git', 'No pushes to display') . '</p>';
                }
            }
        } else {
            $content = '<p>' . dgettext('tuleap-git', 'No pushes to display') . '</p>';
        }
        return $content;
    }

    /**
     * The category of the widget is scm
     *
     * @return String
     */
    public function getCategory()
    {
        return _('Source code management');
    }

    /**
     * Display widget's description
     *
     * @return String
     */
    public function getDescription()
    {
        return dgettext('tuleap-git', 'Display last Git pushes performed by the user');
    }

    /**
     * Update preferences
     *
     * @return bool
     */
    public function updatePreferences(Codendi_Request $request)
    {
        $request->valid(new Valid_String('cancel'));
        $vOffset = new Valid_UInt('plugin_git_user_pushes_offset');
        $vOffset->required();
        $vDays = new Valid_UInt('plugin_git_user_pushes_past_days');
        $vDays->required();
        if (! $request->exist('cancel')) {
            if ($request->valid($vOffset)) {
                $this->offset = $request->get('plugin_git_user_pushes_offset');
            } else {
                $this->offset = 5;
            }
            if ($request->valid($vDays)) {
                $this->pastDays = $request->get('plugin_git_user_pushes_past_days');
            } else {
                $this->pastDays = 30;
            }
            user_set_preference('plugin_git_user_pushes_offset', $this->offset);
            user_set_preference('plugin_git_user_pushes_past_days', $this->pastDays);
        }
        return true;
    }

    public function hasPreferences($widget_id)
    {
        return true;
    }

    public function getPreferences(int $widget_id, int $content_id): string
    {
        $purifier = Codendi_HTMLPurifier::instance();

        return '
            <div class="tlp-form-element">
                <label class="tlp-label" for="offset-' . $widget_id . '">
                    ' . $purifier->purify(dgettext('tuleap-git', 'Maximum number of push by repository')) . '
                </label>
                <input type="number"
                       size="2"
                       class="tlp-input"
                       id="offset-' . $widget_id . '"
                       name="plugin_git_user_pushes_offset"
                       value="' . $purifier->purify($this->offset) . '"
                       placeholder="5">
            </div>
            <div class="tlp-form-element">
                <label class="tlp-label" for="days-' . $widget_id . '">
                    ' . $purifier->purify(dgettext('tuleap-git', 'Maximum number of days ago')) . '
                </label>
                <input type="number"
                       size="2"
                       class="tlp-input"
                       id="days-' . $widget_id . '"
                       name="plugin_git_user_pushes_past_days"
                       value="' . $purifier->purify($this->pastDays) . '"
                       placeholder="30">
            </div>
            ';
    }

    public function getJavascriptDependencies(): array
    {
        return [
            ['file' => RelativeDatesAssetsRetriever::retrieveAssetsUrl(), 'unique-name' => 'tlp-relative-dates'],
        ];
    }

    public function getStylesheetDependencies(): CssAssetCollection
    {
        $include_assets = new IncludeViteAssets(
            __DIR__ . '/../scripts/repository/frontend-assets',
            '/assets/git/repository'
        );

        return new CssAssetCollection([CssViteAsset::fromFileName($include_assets, 'themes/git.scss')]);
    }
}
