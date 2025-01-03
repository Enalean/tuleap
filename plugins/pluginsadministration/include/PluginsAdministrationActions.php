<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

use Tuleap\Plugin\MissingInstallRequirementException;
use Tuleap\PluginsAdministration\PluginDisablerVerifier;

class PluginsAdministrationActions extends Actions
{
    /** @var PluginManager */
    private $plugin_manager;

    /** @var PluginDependencySolver */
    private $dependency_solver;
    /**
     * @var PluginDisablerVerifier
     */
    private $plugin_disabler_verifier;

    public function __construct($controler)
    {
        parent::__construct($controler);
        $this->plugin_manager    = PluginManager::instance();
        $this->dependency_solver = new PluginDependencySolver($this->plugin_manager);
        $plugin_administration   = $this->plugin_manager->getPluginByName('pluginsadministration');
        assert($plugin_administration instanceof PluginsAdministrationPlugin);
        $this->plugin_disabler_verifier = new PluginDisablerVerifier(
            $plugin_administration,
            ForgeConfig::get(PluginDisablerVerifier::SETTING_CANNOT_DISABLE_PLUGINS_WEB_UI)
        );
    }

    public function enable(): void
    {
        $this->checkSynchronizerToken('/plugins/pluginsadministration/');
        $request     = HTTPRequest::instance();
        $plugin_data = $this->_getPluginFromRequest();
        if ($plugin_data) {
            $plugin_manager = $this->plugin_manager;
            $dependencies   = $this->dependency_solver->getDisabledDependencies($plugin_data['plugin']);
            if ($dependencies && ! $request->get('with-dependencies')) {
                $error_msg = sprintf(dgettext('tuleap-pluginsadministration', 'Cannot enable %1$s. Please enable the following plugins before: %2$s'), $plugin_data['plugin']->getName(), implode(', ', $dependencies));
                $GLOBALS['Response']->addFeedback('error', $error_msg);
                return;
            }
            if (! $plugin_manager->isPluginEnabled($plugin_data['plugin'])) {
                try {
                    if ($dependencies) {
                        $plugin_manager->enablePluginAndItsDependencies($plugin_data['plugin']);
                    } else {
                        $plugin_manager->enablePlugin($plugin_data['plugin']);
                    }
                    $GLOBALS['Response']->addFeedback('info', sprintf(dgettext('tuleap-pluginsadministration', '%1$s is now enabled.'), $plugin_data['name']));
                } catch (MissingInstallRequirementException $exception) {
                    $error_msg = sprintf(dgettext('tuleap-pluginsadministration', 'Cannot enable %s. Please deal with the missing requirement first (%s)'), $plugin_data['plugin']->getName(), $exception->getMissingRequirementDescription());
                    $GLOBALS['Response']->addFeedback(Feedback::ERROR, $error_msg);
                }
            }
        }

        if ($request->get('view') === 'properties') {
            $GLOBALS['Response']->redirect('/plugins/pluginsadministration/?view=properties&plugin_id=' . $request->get('plugin_id'));
        }

        $GLOBALS['Response']->redirect('/plugins/pluginsadministration/?view=installed');
    }

    public function install(): void
    {
        $this->checkSynchronizerToken('/plugins/pluginsadministration/');
        $request = HTTPRequest::instance();
        $name    = $request->get('name');
        if ($name) {
            try {
                $plugin = $this->plugin_manager->installPlugin($name);

                if ($plugin) {
                    $GLOBALS['Response']->addFeedback('info', dgettext('tuleap-pluginsadministration', 'The plugin has been successfully installed'));
                    $GLOBALS['Response']->redirect('/plugins/pluginsadministration/?view=properties&plugin_id=' . $plugin->getId());
                }
            } catch (Exception $exception) {
                $GLOBALS['Response']->addFeedback(Feedback::ERROR, sprintf(dgettext('tuleap-pluginsadministration', 'Plugin installation failed: %1s'), $exception->getMessage()));
            }
        }

        $GLOBALS['Response']->redirect('/plugins/pluginsadministration/?view=available');
    }

    public function disable(): void
    {
        $this->checkSynchronizerToken('/plugins/pluginsadministration/');
        $request     = HTTPRequest::instance();
        $plugin_data = $this->_getPluginFromRequest();
        if ($plugin_data && $this->plugin_disabler_verifier->canPluginBeDisabled($plugin_data['plugin'])) {
            $plugin_manager = $this->plugin_manager;
            $dependencies   = $this->dependency_solver->getEnabledDependencies($plugin_data['plugin']);
            if ($dependencies) {
                $error_msg = sprintf(dgettext('tuleap-pluginsadministration', 'Cannot disable %1$s. Please disable the following plugins before: %2$s'), $plugin_data['plugin']->getName(), implode(', ', $dependencies));
                $GLOBALS['Response']->addFeedback('error', $error_msg);
                return;
            }
            if ($plugin_manager->isPluginEnabled($plugin_data['plugin'])) {
                $plugin_manager->disablePlugin($plugin_data['plugin']);
                $GLOBALS['Response']->addFeedback('info', sprintf(dgettext('tuleap-pluginsadministration', '%1$s is now disabled.'), $plugin_data['name']));
            }
        }

        if ($request->get('view') === 'properties') {
            $GLOBALS['Response']->redirect('/plugins/pluginsadministration/?view=properties&plugin_id=' . $request->get('plugin_id'));
        }

        $GLOBALS['Response']->redirect('/plugins/pluginsadministration/?view=installed');
    }

    public function uninstall(): void
    {
        $this->checkSynchronizerToken('/plugins/pluginsadministration/');
        $plugin = $this->_getPluginFromRequest();
        if ($plugin && $this->plugin_disabler_verifier->canPluginBeDisabled($plugin['plugin'])) {
            $plugin_manager = $this->plugin_manager;
            $uninstalled    = $plugin_manager->uninstallPlugin($plugin['plugin']);
            if (! $uninstalled) {
                 $GLOBALS['Response']->addFeedback(Feedback::ERROR, sprintf(dgettext('tuleap-pluginsadministration', 'Plugin "%1$s" have not been uninstalled.'), $plugin['name']));
            } else {
                 $GLOBALS['Response']->addFeedback(Feedback::INFO, sprintf(dgettext('tuleap-pluginsadministration', 'Plugin "%1$s" have been uninstalled.'), $plugin['name']));
            }
        }
    }

    // Secure args: force each value to be an integer.
    public function _validateProjectList($usList)
    {
        $sPrjList = null;
        $usList   = trim(rtrim($usList));
        if ($usList) {
            $usPrjList = explode(',', $usList);
            $sPrjList  = array_map('intval', $usPrjList);
        }
        return $sPrjList;
    }

    public function _addAllowedProjects($prjList)
    {
        $plugin         = $this->_getPluginFromRequest();
        $plugin_manager = $this->plugin_manager;
        $plugin_manager->addProjectForPlugin($plugin['plugin'], $prjList);
    }

    public function _delAllowedProjects($prjList)
    {
        $plugin         = $this->_getPluginFromRequest();
        $plugin_manager = $this->plugin_manager;
        $plugin_manager->delProjectForPlugin($plugin['plugin'], $prjList);
    }

    public function _changePluginGenericProperties($properties)
    {
        if (isset($properties['allowed_project'])) {
            $sPrjList = $this->_validateProjectList($properties['allowed_project']);
            if ($sPrjList !== null) {
                $this->_addAllowedProjects($sPrjList);
            }
        }
        if (isset($properties['disallowed_project'])) {
            $sPrjList = $this->_validateProjectList($properties['disallowed_project']);
            if ($sPrjList !== null) {
                $this->_delAllowedProjects($sPrjList);
            }
        }
        if (isset($properties['prj_restricted'])) {
            $plugin         = $this->_getPluginFromRequest();
            $plugin_manager = $this->plugin_manager;
            $resricted      = ($properties['prj_restricted'] == 1 ? true : false);
            $plugin_manager->updateProjectPluginRestriction($plugin['plugin'], $resricted);
        }
    }

    public function _getPluginFromRequest()
    {
        $return  = false;
        $request = HTTPRequest::instance();
        if ($request->exist('plugin_id') && is_numeric($request->get('plugin_id'))) {
            $plugin_manager = $this->plugin_manager;
            $plugin         = $plugin_manager->getPluginById($request->get('plugin_id'));
            if ($plugin) {
                $plug_info  = $plugin->getPluginInfo();
                $descriptor = $plug_info->getPluginDescriptor();
                $name       = $descriptor->getFullName();
                if (strlen(trim($name)) === 0) {
                    $name = $plugin::class;
                }
                $return           = [];
                $return['name']   = $name;
                $return['plugin'] = $plugin;
            }
        }
        return $return;
    }

    public function setPluginRestriction()
    {
        $request     = HTTPRequest::instance();
        $plugin_id   = $request->get('plugin_id');
        $plugin_data = $this->_getPluginFromRequest();
        $all_allowed = $request->get('all-allowed');

        if ($plugin_data) {
            $plugin = $plugin_data['plugin'];

            $this->checkSynchronizerToken(
                '/plugins/pluginsadministration/?action=set-plugin-restriction&plugin_id=' . $plugin_id
            );

            if ($all_allowed) {
                $this->unsetPluginRestricted($plugin);
            } else {
                $this->setPluginRestricted($plugin);
            }

            $this->redirectToPluginAdministration($plugin_id);
        }
    }

    private function setPluginRestricted(Plugin $plugin)
    {
        if ($this->getPluginResourceRestrictor()->setPluginRestricted($plugin)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                dgettext('tuleap-pluginsadministration', 'Now, only the allowed projects are able to use this plugin.')
            );
        } else {
            $this->sendProjectRestrictedError();
        }
    }

    private function unsetPluginRestricted(Plugin $plugin)
    {
        if ($this->getPluginResourceRestrictor()->unsetPluginRestricted($plugin)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                dgettext('tuleap-pluginsadministration', 'All projects can now use this plugin.')
            );
        } else {
            $this->sendProjectRestrictedError();
        }
    }

    private function sendProjectRestrictedError()
    {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            dgettext('tuleap-pluginsadministration', 'Something went wrong during the update of the plugin restriction status.')
        );
    }

    public function updateAllowedProjectList()
    {
        $request               = HTTPRequest::instance();
        $plugin_id             = $request->get('plugin_id');
        $plugin_data           = $this->_getPluginFromRequest();
        $project_to_add        = $request->get('project-to-allow');
        $project_ids_to_remove = $request->get('project-ids-to-revoke');

        if ($plugin_data) {
            $plugin = $plugin_data['plugin'];

            $this->checkSynchronizerToken('/plugins/pluginsadministration/?action=update-allowed-project-list&plugin_id=' . $plugin_id);

            if ($request->get('allow-project') && ! empty($project_to_add)) {
                $this->allowProjectOnPlugin($plugin, $project_to_add);
            } elseif ($request->get('revoke-project') && ! empty($project_ids_to_remove)) {
                $this->revokeProjectsFromPlugin($plugin, $project_ids_to_remove);
            }
        }

        $this->redirectToPluginAdministration($plugin->getId());
    }

    private function redirectToPluginAdministration($plugin_id)
    {
        $GLOBALS['Response']->redirect(
            '/plugins/pluginsadministration/?view=restrict&plugin_id=' . $plugin_id
        );
    }

    private function allowProjectOnPlugin(Plugin $plugin, $project_to_add)
    {
        $project_manager            = ProjectManager::instance();
        $plugin_resource_restrictor = $this->getPluginResourceRestrictor();
        $project                    = $project_manager->getProjectFromAutocompleter($project_to_add);

        if ($project && $plugin_resource_restrictor->allowProjectOnPlugin($plugin, $project)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                dgettext('tuleap-pluginsadministration', 'Submitted project can now use this plugin.')
            );
        } else {
            $this->sendUpdateProjectListError();
        }
    }

    private function revokeProjectsFromPlugin(Plugin $plugin, $project_ids)
    {
        $plugin_resource_restrictor = $this->getPluginResourceRestrictor();

        if (count($project_ids) > 0 && $plugin_resource_restrictor->revokeProjectsFromPlugin($plugin, $project_ids)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                dgettext('tuleap-pluginsadministration', 'Submitted projects will not be able to use this plugin.')
            );
        } else {
            $this->sendUpdateProjectListError();
        }
    }

    private function sendUpdateProjectListError()
    {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            dgettext('tuleap-pluginsadministration', 'Something went wrong during the update of the allowed project list.')
        );
    }

    private function checkSynchronizerToken($url)
    {
        $token = new CSRFSynchronizerToken($url);
        $token->check();
    }

    private function getPluginResourceRestrictor()
    {
        return new PluginResourceRestrictor(
            new RestrictedPluginDao()
        );
    }
}
