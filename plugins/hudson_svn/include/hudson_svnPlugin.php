<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

require_once __DIR__ . '/../../hudson/include/hudsonPlugin.php';
require_once __DIR__ . '/../../svn/include/svnPlugin.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/constants.php';

use Http\Client\Common\Plugin\CookiePlugin;
use Http\Message\CookieJar;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\HudsonSvn\BuildParams;
use Tuleap\HudsonSvn\ContinuousIntegrationCollector;
use Tuleap\HudsonSvn\Job\Dao as JobDao;
use Tuleap\HudsonSvn\Job\Factory;
use Tuleap\HudsonSvn\Job\Launcher;
use Tuleap\HudsonSvn\Job\Manager;
use Tuleap\HudsonSvn\Plugin\HudsonSvnPluginInfo;
use Tuleap\Jenkins\JenkinsCSRFCrumbRetriever;
use Tuleap\Layout\IncludeAssets;
use Tuleap\SVN\AccessControl\AccessFileHistoryDao;
use Tuleap\SVN\AccessControl\AccessFileHistoryFactory;
use Tuleap\SVN\Dao as SvnDao;
use Tuleap\SVN\Hooks\PostCommit;
use Tuleap\SVN\Repository\Destructor;
use Tuleap\SVN\Repository\RepositoryManager;
use Tuleap\SVN\SvnAdmin;

class hudson_svnPlugin extends Plugin //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{

    public function __construct($id)
    {
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
    public function getDependencies()
    {
        return array('svn', 'hudson');
    }

    /**
     * @return HudsonSvnPluginInfo
     */
    public function getPluginInfo()
    {
        if (!$this->pluginInfo) {
            $this->pluginInfo = new HudsonSvnPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function cssfile($params)
    {
        if (strpos($_SERVER['REQUEST_URI'], HUDSON_BASE_URL) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="' . $this->getAssets()->getFileURL('default-style.css') . '" />';
        }
    }

    public function javascript_file($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (strpos($_SERVER['REQUEST_URI'], HUDSON_BASE_URL) === 0) {
            echo $this->getAssets()->getHTMLSnippet('form.js');
        }
    }

    private function getAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../../../src/www/assets/hudson_svn',
            '/assets/hudson_svn'
        );
    }

    public function collect_ci_triggers($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
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

    private function getJobFactory()
    {
        return new Factory(new JobDao());
    }

    private function getRenderer()
    {
        return TemplateRendererFactory::build()->getRenderer(HUDSON_SVN_BASE_DIR . '/templates');
    }

    private function getRepositoryManager()
    {
        $dao = new SvnDao();

        return new RepositoryManager(
            $dao,
            $this->getProjectManager(),
            $this->getSvnAdmin(),
            SvnPlugin::getLogger(),
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
            SvnPlugin::getLogger()
        );
    }

    private function getSystemCommand()
    {
        return new System_Command();
    }

    private function getSvnAdmin()
    {
        return new SvnAdmin($this->getSystemCommand(), SvnPlugin::getLogger(), Backend::instance(Backend::SVN));
    }

    private function getProjectManager()
    {
        return ProjectManager::instance();
    }

    private function getJobManager()
    {
        return new Manager(new JobDao(), $this->getRepositoryManager(), new SVNPathsUpdater());
    }

    private function isJobValid($job_id)
    {
        return isset($job_id) && !empty($job_id);
    }

    private function isRequestWellFormed(array $params)
    {
        return $this->isJobValid($params['job_id']) &&
               isset($params['request']) &&
               !empty($params['request']);
    }

    private function isPluginConcerned(array $params)
    {
        return $params['request']->get('hudson_use_plugin_svn_trigger_checkbox');
    }

    public function save_ci_triggers($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($this->isRequestWellFormed($params) && $this->isPluginConcerned($params)) {
            $this->getJobManager()->save($params);
        }
    }

    public function update_ci_triggers($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
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

    public function delete_ci_triggers($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($this->isJobValid($params['job_id'])) {
            if (! $this->getJobManager()->delete($params['job_id'])) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_hudson_svn', 'ci_trigger_not_deleted'));
            }
        }
    }

    public function process_post_commit($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $http_client                  = HttpClientFactory::createClient(new CookiePlugin(new CookieJar()));
        $http_request_factory         = HTTPFactoryBuilder::requestFactory();
        $jenkins_csrf_crumb_retriever = new JenkinsCSRFCrumbRetriever($http_client, $http_request_factory);
        $launcher                     = new Launcher(
            $this->getJobFactory(),
            \BackendLogger::getDefaultLogger('hudson_svn_syslog'),
            new Jenkins_Client($http_client, $http_request_factory, HTTPFactoryBuilder::streamFactory(), $jenkins_csrf_crumb_retriever),
            new BuildParams()
        );

        $launcher->launch($params['repository'], $params['commit_info']);
    }
}
