<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

require_once __DIR__ . '/../../hudson/include/hudsonPlugin.class.php';
require_once __DIR__ . '/../../svn/include/svnPlugin.class.php';
require_once 'autoload.php';
require_once 'constants.php';

use Tuleap\HudsonSvn\BuildParams;
use Tuleap\HudsonSvn\Plugin\HudsonSvnPluginInfo;
use Tuleap\HudsonSvn\ContinuousIntegrationCollector;
use Tuleap\HudsonSvn\SvnBackendLogger;
use Tuleap\HudsonSvn\Job\Dao as JobDao;
use Tuleap\HudsonSvn\Job\Manager;
use Tuleap\HudsonSvn\Job\Factory;
use Tuleap\HudsonSvn\Job\Launcher;
use Tuleap\Jenkins\JenkinsCSRFCrumbRetriever;
use Tuleap\Svn\AccessControl\AccessFileHistoryDao;
use Tuleap\Svn\AccessControl\AccessFileHistoryFactory;
use Tuleap\Svn\Admin\Destructor;
use Tuleap\Svn\Repository\RepositoryManager;
use Tuleap\Svn\Hooks\PostCommit;
use Tuleap\Svn\Dao as SvnDao;
use Tuleap\Svn\SvnLogger;
use Tuleap\Svn\SvnAdmin;

class hudson_svnPlugin extends Plugin {

    public function __construct($id) {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);

        $this->addHook('cssfile');
        $this->addHook('javascript_file');

        $this->addHook('collect_ci_triggers');
        $this->addHook('save_ci_triggers');
        $this->addHook('update_ci_triggers');
        $this->addHook('delete_ci_triggers');

        if (defined('SVN_BASE_URL')) {
            $this->addHook(PostCommit::PROCESS_POST_COMMIT);
        }
    }

    /**
     * @see Plugin::getDependencies()
     */
    public function getDependencies() {
        return array('svn', 'hudson');
    }

    /**
     * @return HudsonSvnPluginInfo
     */
    public function getPluginInfo() {
        if (!$this->pluginInfo) {
            $this->pluginInfo = new HudsonSvnPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function cssfile($params) {
        if (strpos($_SERVER['REQUEST_URI'], HUDSON_BASE_URL) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
        }
    }

    public function javascript_file($params) {
        if (strpos($_SERVER['REQUEST_URI'], HUDSON_BASE_URL) === 0) {
            echo '<script type="text/javascript" src="'.$this->getPluginPath().'/scripts/form.js"></script>';
        }
    }

    public function collect_ci_triggers($params)
    {
        $project_id = $params['group_id'];
        $project    = $this->getProjectManager()->getProject($project_id);

        if (! $project->usesService(SvnPlugin::SERVICE_SHORTNAME)) {
            return;
        }

        $collector = new ContinuousIntegrationCollector(
            $this->getRenderer(),
            $this->getRepositoryManager(),
            new JobDao(),
            $this->getJobFactory()
        );
        $job_id    = isset($params['job_id']) ? $params['job_id'] : null;

        $params['services'][] = $collector->collect($project, $job_id);
    }

    private function getJobFactory() {
        return new Factory(new JobDao());
    }

    private function getRenderer() {
        return TemplateRendererFactory::build()->getRenderer(HUDSON_SVN_BASE_DIR.'/templates');
    }

    private function getRepositoryManager()
    {
        $dao = new SvnDao();

        return new RepositoryManager(
            $dao,
            $this->getProjectManager(),
            $this->getSvnAdmin(),
            $this->getLogger(),
            $this->getSystemCommand(),
            $this->getDestructor(),
            EventManager::instance(),
            Backend::instance(Backend::SVN),
            $this->getAccessFileHistoryFactory()
        );
    }

    private function getAccessFileHistoryFactory()
    {
        return new AccessFileHistoryFactory(new AccessFileHistoryDao());
    }

    /**
     * @return Destructor
     */
    private function getDestructor()
    {
        return new Destructor(
            new SvnDao(),
            $this->getLogger()
        );
    }

    private function getSystemCommand()
    {
        return new System_Command();
    }

    private function getLogger()
    {
        return new SvnLogger();
    }

    private function getSvnAdmin()
    {
        return new SvnAdmin($this->getSystemCommand(), $this->getLogger(), Backend::instance(Backend::SVN));
    }

    private function getProjectManager() {
        return ProjectManager::instance();
    }

    private function getJobManager() {
        return new Manager(new JobDao(), $this->getRepositoryManager(), new SVNPathsUpdater());
    }

    private function isJobValid($job_id) {
        return isset($job_id) && !empty($job_id);
    }

    private function isRequestWellFormed(array $params) {
        return $this->isJobValid($params['job_id']) &&
               isset($params['request']) &&
               !empty($params['request']);
    }

    private function isPluginConcerned(array $params) {
        return $params['request']->get('hudson_use_plugin_svn_trigger_checkbox');
    }

    public function save_ci_triggers($params) {
        if ($this->isRequestWellFormed($params) && $this->isPluginConcerned($params)) {
            $this->getJobManager()->save($params);
        }
    }

    public function update_ci_triggers($params) {
        $params['job_id'] = $params['request']->get('job_id');
        if ($this->isRequestWellFormed($params) && $this->isPluginConcerned($params)) {
            $vRepoId = new Valid_UInt('hudson_use_plugin_svn_trigger');
            $vRepoId->required();
            if ($params['request']->valid($vRepoId)) {
                $this->getJobManager()->save($params);
            } else {
                $this->getJobManager()->delete($params['job_id']);
            }
        }
    }

    public function delete_ci_triggers($params) {
        if ($this->isJobValid($params['job_id'])) {
            if (! $this->getJobManager()->delete($params['job_id'])) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_hudson_svn','ci_trigger_not_deleted'));
            }
        }
    }

    public function process_post_commit($params)
    {
        $jenkins_csrf_crumb_retriever = new JenkinsCSRFCrumbRetriever(new Http_Client());
        $launcher                     = new Launcher(
            $this->getJobFactory(),
            new SvnBackendLogger(),
            new Jenkins_Client(new Http_Client(), $jenkins_csrf_crumb_retriever),
            new BuildParams()
        );

        $launcher->launch($params['repository'], $params['commit_info']);
    }
}
