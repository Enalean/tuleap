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

class GitViews_GitPhpViewer {
    /**
     * @var GitRepository
     */
    private $repository;

    /**
     * @var string
     */
    private $gitphp_path = '/usr/share/gitphp-tuleap';

    public function __construct(GitRepository $repository)
    {
        $this->repository  = $repository;
    }

    public function getContent($is_download)
    {
        ob_start();
        $this->getView($is_download);
        return ob_get_clean();
    }

    private function getView($is_download) {
        if ( empty($_REQUEST['a']) )  {
            $_REQUEST['a'] = 'summary';
        }
        set_time_limit(300);
        $_GET['a'] = $_REQUEST['a'];
        $_REQUEST['group_id']      = $this->repository->getProjectId();
        $_REQUEST['repo_id']       = $this->repository->getId();
        $_REQUEST['repo_name']     = $this->repository->getFullName();
        $_GET['p']                 = $_REQUEST['repo_name'].'.git';
        $_REQUEST['repo_path']     = $this->repository->getPath();
        $_REQUEST['project_dir']   = $this->repository->getProject()->getUnixName();
        $_REQUEST['git_root_path'] = $this->repository->getGitRootPath();
        $_REQUEST['action']        = 'view';
        $this->preSanitizeRequestForGitphp();
        if (! $is_download) {
            echo '<div id="gitphp" class="plugin_git_gitphp">';
        }

        $this->executeIndex();

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

    private function executeIndex()
    {
        define('GITPHP_START_TIME', microtime(true));
        define('GITPHP_START_MEM', memory_get_usage());

        define('GITPHP_INCLUDEDIR', __DIR__ . '/../GitPHP/');
        define('GITPHP_GITOBJECTDIR', GITPHP_INCLUDEDIR . 'git/');
        define('GITPHP_CONTROLLERDIR', GITPHP_INCLUDEDIR . 'controller/');
        define('GITPHP_CACHEDIR', GITPHP_INCLUDEDIR . 'cache/');
        define('GITPHP_LOCALEDIR', __DIR__ . '/../../site-content/gitphp_locale/');

        define('GITPHP_BASEDIR', GITPHP_INCLUDEDIR);
        define('GITPHP_CONFIGDIR', GIT_BASE_DIR .'/../etc/');

        require_once GITPHP_INCLUDEDIR . 'Resource.php';

        // Need this include for the compression constants used in the config file
        require_once GITPHP_GITOBJECTDIR . 'Archive.php';

        // Test these executables early
        require_once GITPHP_GITOBJECTDIR . 'GitExe.php';
        require_once GITPHP_GITOBJECTDIR . 'DiffExe.php';


        /*
        * Set the locale based on the user's preference
        */
        if ((!isset($_COOKIE[GITPHP_LOCALE_COOKIE])) || empty($_COOKIE[GITPHP_LOCALE_COOKIE])) {

            /*
             * User's first time here, try by HTTP_ACCEPT_LANGUAGE
             */
            if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                $httpAcceptLang  = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
                $preferredLocale = GitPHP_Resource::FindPreferredLocale($_SERVER['HTTP_ACCEPT_LANGUAGE']);
                if (!empty($preferredLocale)) {
                    //setcookie(GITPHP_LOCALE_COOKIE, $preferredLocale, time()+GITPHP_LOCALE_COOKIE_LIFETIME);
                    GitPHP_Resource::Instantiate($preferredLocale);
                }
            }

            if (!GitPHP_Resource::Instantiated()) {
                /*
                 * Create a dummy cookie to prevent browser delay
                 */
                //setcookie(GITPHP_LOCALE_COOKIE, 0, time()+GITPHP_LOCALE_COOKIE_LIFETIME);
            }

        } else {
            if (isset($_GET['l']) && !empty($_GET['l'])) {

                /*
                 * User picked something
                 */
                //setcookie(GITPHP_LOCALE_COOKIE, $_GET['l'], time()+GITPHP_LOCALE_COOKIE_LIFETIME);
                GitPHP_Resource::Instantiate($_GET['l']);

            } else {
                if (isset($_COOKIE[GITPHP_LOCALE_COOKIE]) && !empty($_COOKIE[GITPHP_LOCALE_COOKIE])) {

                    /*
                     * Returning visitor with a preference
                     */
                    GitPHP_Resource::Instantiate($_COOKIE[GITPHP_LOCALE_COOKIE]);

                }
            }
        }

        try {
            /*
             * Configuration
             */
            GitPHP_Config::GetInstance()->LoadConfig(GITPHP_CONFIGDIR . 'gitphp.conf.php');

            /*
             * Use the default language in the config if user has no preference
             * with en_US as the fallback
             */
            if (!GitPHP_Resource::Instantiated()) {
                GitPHP_Resource::Instantiate(GitPHP_Config::GetInstance()->GetValue('locale', 'en_US'));
            }

            /*
             * Debug
             */
            if (GitPHP_Log::GetInstance()->GetEnabled()) {
                GitPHP_Log::GetInstance()->SetStartTime(GITPHP_START_TIME);
                GitPHP_Log::GetInstance()->SetStartMemory(GITPHP_START_MEM);
            }

            if (!GitPHP_Config::GetInstance()->GetValue('projectroot', null)) {
                throw new GitPHP_MessageException(__('A projectroot must be set in the config'), true, 500);
            }

            /*
             * Check for required executables
             */
            $exe = new GitPHP_GitExe(null);
            if (!$exe->Valid()) {
                throw new GitPHP_MessageException(sprintf(__('Could not run the git executable "%1$s".  You may need to set the "%2$s" config value.'),
                    $exe->GetBinary(), 'gitbin'), true, 500);
            }
            if (!function_exists('xdiff_string_diff')) {
                $exe = new GitPHP_DiffExe();
                if (!$exe->Valid()) {
                    throw new GitPHP_MessageException(sprintf(__('Could not run the diff executable "%1$s".  You may need to set the "%2$s" config value.'),
                        $exe->GetBinary(), 'diffbin'), true, 500);
                }
            }
            unset($exe);

            /*
             * Project list
             */
            if (file_exists(GITPHP_CONFIGDIR . 'projects.conf.php')) {
                GitPHP_ProjectList::Instantiate(GITPHP_CONFIGDIR . 'projects.conf.php', false);
            } else {
                GitPHP_ProjectList::Instantiate(GITPHP_CONFIGDIR . 'gitphp.conf.php', true);
            }

            $controller = GitPHP_Controller::GetController((isset($_GET['a']) ? $_GET['a'] : null));
            if ($controller) {
                $controller->RenderHeaders();
                $controller->Render();
            }

        } catch (Exception $e) {

            if (GitPHP_Config::GetInstance()->GetValue('debug', false)) {
                throw $e;
            }

            if (!GitPHP_Resource::Instantiated()) {
                /*
                 * In case an error was thrown before instantiating
                 * the resource manager
                 */
                GitPHP_Resource::Instantiate('en_US');
            }

            $controller = new GitPHP_Controller_Message();
            $controller->SetParam('message', $e->getMessage());
            if ($e instanceof GitPHP_MessageException) {
                $controller->SetParam('error', $e->Error);
                $controller->SetParam('statuscode', $e->StatusCode);
            } else {
                $controller->SetParam('error', true);
            }
            $controller->RenderHeaders();
            $controller->Render();

        }

        if (GitPHP_Log::GetInstance()->GetEnabled()) {
            $entries = GitPHP_Log::GetInstance()->GetEntries();
            foreach ($entries as $logline) {
                echo "<br />\n" . $logline;
            }
        }
    }
}
