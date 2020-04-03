<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Git\Mirror\MirrorPresenter;
use Tuleap\Layout\IncludeAssets;

class Git_AdminMirrorController //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    /** @var Git_Mirror_MirrorDataMapper */
    private $git_mirror_mapper;

    /** @var CSRFSynchronizerToken */
    private $csrf;

    /** @var Git_MirrorResourceRestrictor */
    private $git_mirror_resource_restrictor;

    /** @var ProjectManager */
    private $project_manager;

    /** @var Git_SystemEventManager */
    private $git_system_event_manager;

    /** @var AdminPageRenderer */
    private $admin_page_renderer;
    /**
     * @var IncludeAssets
     */
    private $include_assets;

    public function __construct(
        CSRFSynchronizerToken $csrf,
        Git_Mirror_MirrorDataMapper $git_mirror_mapper,
        Git_MirrorResourceRestrictor $git_mirror_resource_restrictor,
        ProjectManager $project_manager,
        Git_SystemEventManager $git_system_event_manager,
        AdminPageRenderer $admin_page_renderer,
        IncludeAssets $include_assets
    ) {
        $this->csrf                           = $csrf;
        $this->git_mirror_mapper              = $git_mirror_mapper;
        $this->git_mirror_resource_restrictor = $git_mirror_resource_restrictor;
        $this->project_manager                = $project_manager;
        $this->git_system_event_manager       = $git_system_event_manager;
        $this->admin_page_renderer            = $admin_page_renderer;
        $this->include_assets                 = $include_assets;
    }

    public function process(Codendi_Request $request)
    {
        if ($request->get('action') == 'add-mirror') {
            $this->createMirror($request);
        } elseif ($request->get('action') == 'modify-mirror') {
            $this->modifyMirror($request);
        } elseif ($request->get('action') == 'delete-mirror') {
            $this->deleteMirror($request);
        } elseif ($request->get('action') == 'set-mirror-restriction') {
            $this->setMirrorRestriction($request);
        } elseif ($request->get('action') == 'update-allowed-project-list') {
            $this->updateAllowedProjectList($request);
        } elseif ($request->get('action') == 'dump-gitolite-conf') {
            $this->askForAGitoliteDumpConf();
        }
    }

    public function display(Codendi_Request $request)
    {
        $title         = dgettext('tuleap-git', 'Git');
        $template_path = dirname(GIT_BASE_DIR) . '/templates';
        $presenter     = null;

        switch ($request->get('action')) {
            case 'manage-allowed-projects':
                $GLOBALS['HTML']->includeFooterJavascriptFile('/scripts/tuleap/manage-allowed-projects-on-resource.js');

                $presenter     = $this->getManageAllowedProjectsPresenter($request);
                $template_path = ForgeConfig::get('codendi_dir') . '/src/templates/resource_restrictor';
                $this->renderAPresenter($title, $template_path, $presenter);
                break;
            default:
                $GLOBALS['HTML']->includeFooterJavascriptFile($this->include_assets->getFileURL('siteadmin-mirror.js'));

                $presenter = $this->getAllMirrorsPresenter($title);
                $this->renderANoFramedPresenter($title, $template_path, $presenter);
                break;
        }
    }

    private function renderAPresenter($title, $template_path, $presenter)
    {
        $this->admin_page_renderer->renderAPresenter(
            $title,
            $template_path,
            $presenter->getTemplate(),
            $presenter
        );
    }

    private function renderANoFramedPresenter($title, $template_path, $presenter)
    {
        $this->admin_page_renderer->renderANoFramedPresenter(
            $title,
            $template_path,
            $presenter->getTemplate(),
            $presenter
        );
    }

    private function getAllMirrorsPresenter($title)
    {
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
    private function getMirrorPresenters(array $mirrors)
    {
        $mirror_presenters = array();
        foreach ($mirrors as $mirror) {
            $mirror_presenters[] = new MirrorPresenter(
                $mirror,
                $this->git_mirror_mapper->fetchRepositoriesPerMirrorPresenters($mirror)
            );
        }
        return $mirror_presenters;
    }

    private function getMirrorFromRequest(Codendi_Request $request)
    {
        try {
            $mirror_id = $request->get('mirror_id');
            return $this->git_mirror_mapper->fetch($mirror_id);
        } catch (Git_Mirror_MirrorNotFoundException $e) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, dgettext('tuleap-git', 'Mirror not found, please try again'));
            $GLOBALS['Response']->redirect('?pane=mirrors_admin');
        }
    }

    private function getManageAllowedProjectsPresenter(Codendi_Request $request)
    {
        $mirror = $this->getMirrorFromRequest($request);

        return new Git_AdminMAllowedProjectsPresenter(
            $mirror,
            $this->git_mirror_resource_restrictor->searchAllowedProjectsOnMirror($mirror),
            $this->git_mirror_resource_restrictor->isMirrorRestricted($mirror)
        );
    }

    private function setMirrorRestriction($request)
    {
        $mirror      =  $mirror = $this->getMirrorFromRequest($request);
        $all_allowed = $request->get('all-allowed');

        $this->checkSynchronizerToken(GIT_SITE_ADMIN_BASE_URL . '?view=mirrors_restriction&action=set-mirror-restriction&mirror_id=' . $mirror->id);

        if ($all_allowed) {
            if ($this->git_mirror_resource_restrictor->unsetMirrorRestricted($mirror)) {
                $GLOBALS['Response']->addFeedback(Feedback::INFO, dgettext('tuleap-git', 'All projects can now use this mirror.'));
                $GLOBALS['Response']->redirect(GIT_SITE_ADMIN_BASE_URL . '?view=mirrors_restriction&action=manage-allowed-projects&mirror_id=' . $mirror->id);
            }
        } else {
            if (
                $this->git_mirror_resource_restrictor->setMirrorRestricted($mirror) &&
                $this->git_mirror_mapper->deleteFromDefaultMirrors($mirror->id)
            ) {
                $GLOBALS['Response']->addFeedback(Feedback::INFO, dgettext('tuleap-git', 'Now, only the allowed projects are able to use this mirror. Projects with at least one repository using this mirror have been automatically added to the list of allowed projects.'));
                $GLOBALS['Response']->redirect(GIT_SITE_ADMIN_BASE_URL . '?view=mirrors_restriction&action=manage-allowed-projects&mirror_id=' . $mirror->id);
            }
        }

        $GLOBALS['Response']->addFeedback(Feedback::ERROR, dgettext('tuleap-git', 'Something went wrong during the update of the mirror restriction status.'));
        $GLOBALS['Response']->redirect(GIT_SITE_ADMIN_BASE_URL . '?view=mirrors_restriction&action=manage-allowed-projects&mirror_id=' . $mirror->id);
    }

    private function askForAGitoliteDumpConf()
    {
        $this->csrf->check();

        $this->git_system_event_manager->queueDumpOfAllMirroredRepositories();
        $GLOBALS['Response']->addFeedback(Feedback::INFO, sprintf(dgettext('tuleap-git', 'A system event has been queued. The gitolite configuration will be dumped at the time of its execution. <a href="%1$s">Git queue can be found here</a>.'), $this->getGitSystemEventsQueueURL()), CODENDI_PURIFIER_DISABLED);
        $GLOBALS['Response']->redirect(GIT_SITE_ADMIN_BASE_URL . '?pane=mirrors_admin');
    }

    private function getGitSystemEventsQueueURL()
    {
        return "/admin/system_events/?queue=git";
    }

    private function updateAllowedProjectList($request)
    {
        $mirror                = $this->getMirrorFromRequest($request);
        $project_to_add        = $request->get('project-to-allow');
        $project_ids_to_remove = $request->get('project-ids-to-revoke');

        $this->checkSynchronizerToken(GIT_SITE_ADMIN_BASE_URL . '?view=mirrors_restriction&action=update-allowed-project-list&mirror_id=' . $mirror->id);

        if ($request->get('allow-project') && ! empty($project_to_add)) {
            $this->allowProjectOnMirror($mirror, $project_to_add);
        } elseif ($request->get('revoke-project') && ! empty($project_ids_to_remove)) {
            $this->revokeProjectsFromMirror($mirror, $project_ids_to_remove);
        }
    }

    private function allowProjectOnMirror(Git_Mirror_Mirror $mirror, $project_to_add)
    {
        $project = $this->project_manager->getProjectFromAutocompleter($project_to_add);

        if ($project && $this->git_mirror_resource_restrictor->allowProjectOnMirror($mirror, $project)) {
            $GLOBALS['Response']->addFeedback('info', dgettext('tuleap-git', 'Submitted project can now use this mirror.'));
            $GLOBALS['Response']->redirect(GIT_SITE_ADMIN_BASE_URL . '?view=mirrors_restriction&action=manage-allowed-projects&mirror_id=' . $mirror->id);
        }

        $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-git', 'Something went wrong during the update of the allowed project list.'));
        $GLOBALS['Response']->redirect(GIT_SITE_ADMIN_BASE_URL . '?view=mirrors_restriction&action=manage-allowed-projects&mirror_id=' . $mirror->id);
    }

    private function revokeProjectsFromMirror(Git_Mirror_Mirror $mirror, $project_ids)
    {
        if (
            count($project_ids) > 0 &&
            $this->git_mirror_resource_restrictor->revokeProjectsFromMirror($mirror, $project_ids) &&
            $this->git_mirror_mapper->deleteFromDefaultMirrorsInProjects($mirror, $project_ids)
        ) {
            $GLOBALS['Response']->addFeedback('info', dgettext('tuleap-git', 'Submitted projects will not be able to use this mirror.'));
            $GLOBALS['Response']->redirect(GIT_SITE_ADMIN_BASE_URL . '?view=mirrors_restriction&action=manage-allowed-projects&mirror_id=' . $mirror->id);
        }

        $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-git', 'Something went wrong during the update of the allowed project list.'));
        $GLOBALS['Response']->redirect(GIT_SITE_ADMIN_BASE_URL . '?view=mirrors_restriction&action=manage-allowed-projects&mirror_id=' . $mirror->id);
    }

    private function checkSynchronizerToken($url)
    {
        $token = new CSRFSynchronizerToken($url);
        $token->check();
    }

    private function createMirror(Codendi_Request $request)
    {
        $url      = $request->get('new_mirror_url');
        $hostname = $request->get('new_mirror_hostname');
        $ssh_key  = $request->get('new_mirror_key');
        $password = $request->get('new_mirror_pwd');
        $name     = $request->get('new_mirror_name');

        $this->csrf->check();

        try {
            $this->git_mirror_mapper->save($url, $hostname, $ssh_key, $password, $name);
        } catch (Git_Mirror_MissingDataException $e) {
            $this->redirectToCreateWithError(dgettext('tuleap-git', 'All fields are required'));
        } catch (Git_Mirror_CreateException $e) {
            $this->redirectToCreateWithError(dgettext('tuleap-git', 'Failed to add new mirror'));
        } catch (Git_Mirror_HostnameAlreadyUsedException $e) {
            $this->redirectToCreateWithError(dgettext('tuleap-git', 'Hostname must be unique!'));
        } catch (Git_Mirror_HostnameIsReservedException $e) {
            $this->redirectToCreateWithError(sprintf(dgettext('tuleap-git', 'Hostname (%1$s) is reserved!'), $hostname));
        }
    }

    private function redirectToCreateWithError($message)
    {
        $GLOBALS['Response']->addFeedback('error', $message);
        $GLOBALS['Response']->redirect("?pane=mirrors_admin&action=show-add-mirror");
    }

    private function modifyMirror(Codendi_Request $request)
    {
        $this->csrf->check();
        $mirror_id = $request->get('mirror_id');
        try {
            $update    = $this->git_mirror_mapper->update(
                $mirror_id,
                $request->get('mirror_url'),
                $request->get('mirror_hostname'),
                $request->get('mirror_key'),
                $request->get('mirror_name')
            );

            if (! $update) {
                $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-git', 'Failed to update mirror'));
            } else {
                $GLOBALS['Response']->addFeedback('info', dgettext('tuleap-git', 'Mirror updated!'));
            }
        } catch (Git_Mirror_MirrorNotFoundException $e) {
            $this->redirectToEditFormWithError($mirror_id, dgettext('tuleap-git', 'Failed to update mirror'));
        } catch (Git_Mirror_MirrorNoChangesException $e) {
            $this->redirectToEditFormWithError($mirror_id, dgettext('tuleap-git', 'No changes for mirror'));
        } catch (Git_Mirror_MissingDataException $e) {
            $this->redirectToEditFormWithError($mirror_id, dgettext('tuleap-git', 'All fields are required'));
        } catch (Git_Mirror_HostnameAlreadyUsedException $e) {
            $this->redirectToEditFormWithError($mirror_id, dgettext('tuleap-git', 'Hostname must be unique!'));
        } catch (Git_Mirror_HostnameIsReservedException $e) {
            $this->redirectToEditFormWithError($mirror_id, sprintf(dgettext('tuleap-git', 'Hostname (%1$s) is reserved!'), $request->get('mirror_hostname')));
        }
    }

    private function redirectToEditFormWithError($mirror_id, $message)
    {
        $GLOBALS['Response']->addFeedback('error', $message);
        $GLOBALS['Response']->redirect("?pane=mirrors_admin&action=show-edit-mirror&mirror_id=" . $mirror_id);
    }

    private function deleteMirror(Codendi_Request $request)
    {
        try {
            $this->csrf->check();

            $id     = $request->get('mirror_id');
            $delete = $this->git_mirror_mapper->delete($id);

            if (! $delete) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    dgettext('tuleap-git', 'Failed to delete mirror')
                );

                return;
            }

            if (! $this->git_mirror_mapper->deleteFromDefaultMirrors($id)) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    dgettext('tuleap-git', 'An error occured whiled deleting the mirror in the default mirrors for projects.')
                );
            }
        } catch (Git_Mirror_MirrorNotFoundException $e) {
            $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-git', 'Failed to delete mirror'));
        }

        $GLOBALS['Response']->redirect(GIT_SITE_ADMIN_BASE_URL . '?pane=mirrors_admin');
    }
}
