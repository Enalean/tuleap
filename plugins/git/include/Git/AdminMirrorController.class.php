<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class Git_AdminMirrorController {

    /** @var Git_Mirror_MirrorDataMapper */
    private $git_mirror_mapper;

    /** @var CSRFSynchronizerToken */
    private $csrf;

    public function __construct(CSRFSynchronizerToken $csrf, Git_Mirror_MirrorDataMapper $git_mirror_mapper) {
        $this->csrf              = $csrf;
        $this->git_mirror_mapper = $git_mirror_mapper;
    }

    public function process(Codendi_Request $request) {
        if ($request->get('action') == 'add-mirror') {
            $this->createMirror($request);
        } elseif ($request->get('action') == 'show-add-mirror') {
            $this->showAddMirror();
        } elseif ($request->get('action') == 'show-edit-mirror') {
            $this->showEditMirror($request);
        } elseif ($request->get('action') == 'modify-mirror' && $request->get('update_mirror')) {
            $this->modifyMirror($request);
        } elseif ($request->get('action') == 'modify-mirror' && $request->get('delete_mirror')) {
            $this->deleteMirror($request);
        }
    }

    public function display(Codendi_Request $request) {
        $title    = $GLOBALS['Language']->getText('plugin_git', 'descriptor_name');
        $renderer = TemplateRendererFactory::build()->getRenderer(dirname(GIT_BASE_DIR).'/templates');

        switch ($request->get('action')) {
            case 'list-repositories':
                $presenter = $this->getListRepositoriesPresenter($request);
                break;
            default:
                $presenter = $this->getAllMirrorsPresenter($title);
                break;

        }
        $GLOBALS['HTML']->header(array('title' => $title, 'selected_top_tab' => 'admin'));
        $renderer->renderToPage($presenter::TEMPLATE, $presenter);
        $GLOBALS['HTML']->footer(array());
    }

    private function getAllMirrorsPresenter($title) {
        return new Git_AdminMirrorListPresenter(
            $title,
            $this->csrf,
            $this->getMirrorPresenters($this->git_mirror_mapper->fetchAll())
        );
    }

    /**
     * @param Git_Mirror_Mirror[] $mirrors
     * @return array
     */
    private function getMirrorPresenters(array $mirrors) {
        $mirror_presenters = array();
        foreach($mirrors as $mirror) {
            $mirror_presenters[] = array(
                'id'                     => $mirror->id,
                'url'                    => $mirror->url,
                'owner_id'               => $mirror->owner_id,
                'owner_name'             => $mirror->owner_name,
                'ssh_key_value'          => $mirror->ssh_key,
                'ssh_key_ellipsis_value' => substr($mirror->ssh_key, 0, 40).'...'.substr($mirror->ssh_key, -40),
            );
        }
        return $mirror_presenters;
    }

    private function getListRepositoriesPresenter(Codendi_Request $request) {
        $mirror_id = $request->get('mirror_id');

        $mirror = $this->git_mirror_mapper->fetch($mirror_id);

        return new Git_AdminMRepositoryListPresenter(
            $mirror->url,
            $this->git_mirror_mapper->fetchRepositoriesPerMirrorPresenters($mirror)
        );
    }

    private function createMirror(Codendi_Request $request) {
        $url      = $request->get('new_mirror_url');
        $ssh_key  = $request->get('new_mirror_key');
        $password = $request->get('new_mirror_pwd');

        $this->csrf->check();

        try {
            $this->git_mirror_mapper->save($url, $ssh_key, $password);
        } catch (Git_Mirror_MissingDataException $e) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_git','admin_mirror_fields_required'));
        } catch (Git_Mirror_CreateException $e) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_git','admin_mirror_save_failed'));
        }
    }

    private function showAddMirror() {
        $title    = $GLOBALS['Language']->getText('plugin_git', 'descriptor_name');
        $renderer = TemplateRendererFactory::build()->getRenderer(dirname(GIT_BASE_DIR).'/templates');

        $admin_presenter = new Git_AdminMirrorAddPresenter(
            $title,
            $this->csrf
        );

        $GLOBALS['HTML']->header(array('title' => $title, 'selected_top_tab' => 'admin'));
        $renderer->renderToPage('admin-plugin', $admin_presenter);
        $GLOBALS['HTML']->footer(array());
    }

    private function showEditMirror(Codendi_Request $request) {
        $title    = $GLOBALS['Language']->getText('plugin_git', 'descriptor_name');
        $renderer = TemplateRendererFactory::build()->getRenderer(dirname(GIT_BASE_DIR).'/templates');

        try {
            $mirror = $this->git_mirror_mapper->fetch($request->get('mirror_id'));
        } catch (Git_Mirror_MirrorNotFoundException $e) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_git','admin_mirror_cannot_update'));
            $GLOBALS['Response']->redirect('/plugins/git/admin/?pane=mirrors_admin');
        }

        $admin_presenter = new Git_AdminMirrorEditPresenter(
            $title,
            $this->csrf,
            $mirror
        );

        $GLOBALS['HTML']->header(array('title' => $title, 'selected_top_tab' => 'admin'));
        $renderer->renderToPage('admin-plugin', $admin_presenter);
        $GLOBALS['HTML']->footer(array());
    }

    private function modifyMirror(Codendi_Request $request) {
        try {
            $this->csrf->check();

            $update = $this->git_mirror_mapper->update(
                $request->get('mirror_id'),
                $request->get('mirror_url'),
                $request->get('mirror_key')
            );

            if (! $update) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_git','admin_mirror_cannot_update'));
            } else  {
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_git','admin_mirror_updated'));
            }
        } catch (Git_Mirror_MirrorNotFoundException $e) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_git','admin_mirror_cannot_update'));
        } catch (Git_Mirror_MirrorNoChangesException $e) {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_git','admin_mirror_no_changes'));
        } catch (Git_Mirror_MissingDataException $e) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_git','admin_mirror_fields_required'));
        }
    }

    private function deleteMirror(Codendi_Request $request) {
        try {
            $this->csrf->check();

            $id     = $request->get('mirror_id');
            $delete = $this->git_mirror_mapper->delete($id);

            if (! $delete) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_git','admin_mirror_cannot_delete'));
            }
        } catch (Git_Mirror_MirrorNotFoundException $e) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_git','admin_mirror_cannot_delete'));
        }

        $GLOBALS['Response']->redirect('/plugins/git/admin/?pane=mirrors_admin');
    }
}
