<?php
/**
 * Copyright (c) Enalean, 2013 - 2018. All Rights Reserved.
 * Copyright (c) 2010 Christopher Han
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

use Tuleap\Git\GitPHP\Config;
use Tuleap\Git\GitPHP\Controller;
use Tuleap\Git\GitPHP\Controller_Message;
use Tuleap\Git\GitPHP\DiffExe;
use Tuleap\Git\GitPHP\GitExe;
use Tuleap\Git\GitPHP\Log;
use Tuleap\Git\GitPHP\MessageException;
use Tuleap\Git\GitPHP\ProjectList;
use Tuleap\Git\GitPHP\Resource;

class GitViews_GitPhpViewer {
    /**
     * @var GitRepository
     */
    private $repository;
    /**
     * @var PFUser
     */
    private $current_user;

    public function __construct(GitRepository $repository, PFUser $current_user)
    {
        $this->repository   = $repository;
        $this->current_user = $current_user;
    }

    public function getContent($is_download)
    {
        if ( empty($_REQUEST['a']) )  {
            $_REQUEST['a'] = 'summary';
        }
        set_time_limit(300);
        $_GET['a']            = $_REQUEST['a'];
        $_REQUEST['group_id'] = $this->repository->getProjectId();
        $_GET['p']            = $this->repository->getFullName() . '.git';
        $_REQUEST['action']   = 'view';
        $this->preSanitizeRequestForGitphp();
        if (! $is_download) {
            echo '<div id="gitphp" class="plugin_git_gitphp">';
        }

        $this->displayGitPHP();

        if (! $is_download) {
            echo '</div>';
        }
    }

    private function preSanitizeRequestForGitphp() {
        $hp = Codendi_HTMLPurifier::instance();
        foreach(array('h', 'hb', 'hp') as $parameter) {
            if (isset($_REQUEST[$parameter])) {
                $_GET[$parameter] = $hp->purify($_REQUEST[$parameter]);
            }
        }
    }

    private function displayGitPHP()
    {
        define('GITPHP_START_TIME', microtime(true));
        define('GITPHP_START_MEM', memory_get_usage());

        define('GITPHP_INCLUDEDIR', __DIR__ . '/../GitPHP/');
        define('GITPHP_GITOBJECTDIR', GITPHP_INCLUDEDIR . 'git/');
        define('GITPHP_CONTROLLERDIR', GITPHP_INCLUDEDIR . 'controller/');
        define('GITPHP_CACHEDIR', GITPHP_INCLUDEDIR . 'cache/');
        define('GITPHP_LOCALEDIR', __DIR__ . '/../../site-content/gitphp_locale/');

        define('GITPHP_BASEDIR', GITPHP_INCLUDEDIR);

        require_once GITPHP_INCLUDEDIR . 'Resource.php';

        // Need this include for the compression constants used in the config file
        require_once GITPHP_GITOBJECTDIR . 'Archive.php';

        // Test these executables early
        require_once GITPHP_GITOBJECTDIR . 'GitExe.php';
        require_once GITPHP_GITOBJECTDIR . 'DiffExe.php';

        Resource::Instantiate($this->current_user->getLanguageID());

        try {
            $this->setupGitPHPConfiguration();
            /*
             * Use the default language in the config if user has no preference
             * with en_US as the fallback
             */
            if (! Resource::Instantiated()) {
                 Resource::Instantiate( Config::GetInstance()->GetValue('locale', 'en_US'));
            }

            /*
             * Debug
             */
            if (Log::GetInstance()->GetEnabled()) {
                Log::GetInstance()->SetStartTime(GITPHP_START_TIME);
                Log::GetInstance()->SetStartMemory(GITPHP_START_MEM);
            }

            if (! Config::GetInstance()->GetValue('projectroot', null)) {
                throw new MessageException(Tuleap\Git\GitPHP\__('A projectroot must be set in the config'), true, 500);
            }

            /*
             * Check for required executables
             */
            $exe = new GitExe(null);
            if (!$exe->Valid()) {
                throw new MessageException(sprintf(Tuleap\Git\GitPHP\__('Could not run the git executable "%1$s".  You may need to set the "%2$s" config value.'),
                    $exe->GetBinary(), 'gitbin'), true, 500);
            }
            if (!function_exists('xdiff_string_diff')) {
                $exe = new DiffExe();
                if (!$exe->Valid()) {
                    throw new MessageException(sprintf(Tuleap\Git\GitPHP\__('Could not run the diff executable "%1$s".  You may need to set the "%2$s" config value.'),
                        $exe->GetBinary(), 'diffbin'), true, 500);
                }
            }
            unset($exe);

            /*
             * Project list
             */
            ProjectList::Instantiate(null, true);

            $controller = Controller::GetController((isset($_GET['a']) ? $_GET['a'] : null));
            if ($controller) {
                $controller->RenderHeaders();
                $controller->Render();
            }

        } catch (Exception $e) {

            if (Config::GetInstance()->GetValue('debug', false)) {
                throw $e;
            }

            if (! Resource::Instantiated()) {
                /*
                 * In case an error was thrown before instantiating
                 * the resource manager
                 */
                Resource::Instantiate('en_US');
            }

            $controller = new Controller_Message();
            $controller->SetParam('message', $e->getMessage());
            if ($e instanceof MessageException) {
                $controller->SetParam('error', $e->Error);
                $controller->SetParam('statuscode', $e->StatusCode);
            } else {
                $controller->SetParam('error', true);
            }
            $controller->RenderHeaders();
            $controller->Render();

        }

        if (Log::GetInstance()->GetEnabled()) {
            $entries = Log::GetInstance()->GetEntries();
            foreach ($entries as $logline) {
                echo "<br />\n" . $logline;
            }
        }
    }

    private function setupGitPHPConfiguration()
    {
        $config = Config::GetInstance();
        $config->SetValue('stylesheet', '');
        $config->SetValue('projectroot', $this->repository->getGitRootPath() . $this->repository->getProject()->getUnixName() . '/');
        $config->SetValue('gitbin', '/usr/bin/git');
        $config->SetValue('diffbin', '/usr/bin/diff');
        $config->SetValue('gittmp', '/tmp/');
        $config->SetValue('title', 'Tuleap');
        $config->SetValue('compressformat', GITPHP_COMPRESS_BZ2);
        $config->SetValue('compresslevel', 9);
        $config->SetValue('geshi', true);
        $config->SetValue('filemimetype', true);
        $config->SetValue('magicdb', '/usr/share/misc/magic.mgc');
        $config->SetValue('search', true);
        $config->SetValue('filesearch', false);
        $config->SetValue('debug', false);
        $config->SetValue('cache', false);
        $config->SetValue('cacheexpire', false);
        $config->SetValue('cachelifetime', 3600);
        $config->SetValue('smarty_tmp', '/tmp/gitphp-tuleap/smarty');
    }
}
