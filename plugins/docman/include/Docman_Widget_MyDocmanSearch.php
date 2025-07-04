<?php
/**
 * Copyright (c) STMicroelectronics, 2004-2009. All rights reserved
 * Copyright (c) Enalean, 2017-Present. All rights reserved
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

/**
* Docman_Widget_MyDocmanSearch
*/
class Docman_Widget_MyDocmanSearch extends Widget //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    public $pluginPath;

    public function __construct($pluginPath)
    {
        parent::__construct('plugin_docman_mydocman_search');
        $this->_pluginPath = $pluginPath;
    }

    public function getTitle()
    {
        return dgettext('tuleap-docman', 'Document Id Search');
    }

    public function getContent(): string
    {
        $html    = '';
        $request = HTTPRequest::instance();
        $um      = UserManager::instance();
        $user    = $um->getCurrentUser();

        $vFunc = new Valid_WhiteList('docman_func', ['show_docman']);
        $vFunc->required();
        if ($request->valid($vFunc)) {
            $func = $request->get('docman_func');
        } else {
            $func = '';
        }
        $vDocmanId = new Valid_UInt('docman_id');
        $vDocmanId->required();
        if ($request->valid($vDocmanId)) {
            $docman_id = $request->get('docman_id');
        } else {
            $docman_id = '';
        }

        $url = '';
        if ($request->get('dashboard_id')) {
            $url = '?dashboard_id=' . urlencode($request->get('dashboard_id'));
        }

        $is_searching      = $func === 'show_docman' && $docman_id;
        $matching_document = $is_searching ? $this->getMatchingDocumentPresenter((int) $docman_id, $user) : null;

        $renderer = TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates/widget');
        $html    .= $renderer->renderToString('my-docman-search', [
            'form_url'          => $url,
            'docman_id'         => $docman_id,
            'is_searching'      => $is_searching,
            'matching_document' => $matching_document,
        ]);

        return $html;
    }

    /**
     * @psalm-return array{url: string, title: string}|null
     */
    private function getMatchingDocumentPresenter(int $item_id, PFUser $user): ?array
    {
        $res = $this->returnAllowedGroupId($item_id, $user);
        if (! $res) {
            return null;
        }

        $project        = ProjectManager::instance()->getProject($res['group_id']);
        $docman_service = $project->getService(DocmanPlugin::SERVICE_SHORTNAME);
        if (! $docman_service) {
            return null;
        }
        assert($docman_service instanceof \Tuleap\Docman\ServiceDocman);

        $permissions_manager = Docman_PermissionsManager::instance((int) $project->getID());
        if (! $permissions_manager->userCanAccess($user, $item_id)) {
            return null;
        }

        $item_url = $docman_service->getUrl();
        if ($res['parent_id']) {
            $item_url .= 'preview/' . urlencode((string) $item_id);
        }

        return [
            'url'   => $item_url,
            'title' => $res['title'],
        ];
    }

    /**
     * Check if given document is in a project readable by user.
     *
     * Returns project info if:
     * * the document belongs to a public project
     * ** And the user is active (not restricted)
     * ** Or user is restricted but member of the project.
     * * or a private one and the user is a member of it
     * else 0
     *
     * @param $docman_id int  Document Id
     * @param $user      User User Id
     * @return array|0
     * @psalm-return array{group_id: int, parent_id: int, title:string}|0
     **/
    public function returnAllowedGroupId($docman_id, $user)
    {
        $sql_group = 'SELECT group_id, parent_id, title FROM  plugin_docman_item WHERE' .
                         ' item_id = ' . db_ei($docman_id);

        $res_group = db_query($sql_group);

        if ($res_group && db_numrows($res_group) == 1) {
            $row = db_fetch_array($res_group);
            $res = [
                'group_id'  => (int) $row['group_id'],
                'title'     => (string) $row['title'],
                'parent_id' => (int) $row['parent_id'],
            ];

            $project = ProjectManager::instance()->getProject($res['group_id']);
            if ($project->isPublic()) {
                // Check restricted user
                if (($user->isRestricted() && $user->isMember($res['group_id'])) || ! $user->isRestricted()) {
                    return $res;
                }
            } else {
                if ($user->isMember($res['group_id'])) {
                    return $res;
                }
            }
        }
        return 0;
    }

    public function getCategory()
    {
        return dgettext('tuleap-docman', 'Document manager');
    }

    public function getDescription()
    {
        return dgettext('tuleap-docman', 'Redirect to document with given id.');
    }
}
