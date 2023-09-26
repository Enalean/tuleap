<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Hudson\HudsonJobBuilder;
use Tuleap\Sanitizer\URISanitizer;

require_once __DIR__ . '/../../../src/www/include/help.php';

class hudsonViews extends Views
{
    public function __construct(&$controler, $view = null)
    {
        $this->View($controler, $view);
    }

    public function header()
    {
        $request  = HTTPRequest::instance();
        $purifier = Codendi_HTMLPurifier::instance();
        $locale   = $request->getCurrentUser()->getShortLocale();
        $GLOBALS['HTML']->addToolbarItem('<a data-help-window href="' .
                                         $purifier->purify('/doc/' . urlencode($locale) . '/user-guide/ci.html')
                                         . '">' . $purifier->purify($GLOBALS['Language']->getText('global', 'help')) . '</a>');
        $GLOBALS['HTML']->header(['title' => $this->_getTitle(), 'project' => $request->getProject(), 'toptab' => 'hudson', [], 'body_class' => ['continuous-integration-body']]);
        echo '<h2 class="almost-tlp-title project-header-title">' . $this->_getTitle() . '</h2>';
    }

    public function _getTitle()
    {
        return dgettext('tuleap-hudson', 'Continuous Integration');
    }

    public function _getHelp($section = '', $questionmark = false)
    {
        if (trim($section) !== '' && $section[0] !== '#') {
            $section = '#' . $section;
        }
        if ($questionmark) {
            $help_label = '[?]';
        } else {
            $help_label = $GLOBALS['Language']->getText('global', 'help');
        }
        return help_button('ci.html' . $section, $help_label);
    }

    public function footer()
    {
        $GLOBALS['HTML']->footer([]);
    }

    // {{{ Views
    public function projectOverview()
    {
        $request  = HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $user     = UserManager::instance()->getCurrentUser();
        $em       = EventManager::instance();
        $services = [];
        $params   = ['group_id' => $group_id, 'services' => &$services];
        /* $services will contain an array of details of all plugins that will trigger CI builds
           Example of what $services may contain:
            Array(
                [0] => Array(
                        [service] => plugin1
                        [title] => title1
                        [used] => Array(
                                [job_id_11] => true
                                [job_id_12] => true
                            )
                        [add_form]  => "html form"
                        [edit_form] => "html form"
                    )
                [1] => Array(
                        [service] => plugin2
                        [title] => title2
                        [used] => Array(
                                [job_id_21] => true
                                [job_id_22] => true
                            )
                        [add_form]  => "html form"
                        [edit_form] => "html form"
                    )
            )
        */
        echo '<div class="continuous-integration-content">';
        $em->processEvent('collect_ci_triggers', $params);
        $this->_display_jobs_table($group_id, $services);
        if ($user->isMember($request->get('group_id'), 'A')) {
            $this->_display_add_job_form($group_id, $services);
        }
        echo '</div>';
    }

    public function job_details()
    {
        $request  = HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $job_dao  = new PluginHudsonJobDao(CodendiDataAccess::instance());
        if ($request->exist('job_id')) {
            $job_id = $request->get('job_id');
            $dar    = $job_dao->searchByJobID($job_id);
        } elseif ($request->exist('job')) {
            // used for references (job #MyJob or job #myproject:MyJob)
            $job_name = $request->get('job');
            $dar      = $job_dao->searchByJobName($job_name, $group_id);
        }
        if ($dar->valid()) {
            $row           = $dar->current();
            $crossref_fact = new CrossReferenceFactory($row['name'], 'hudson_job', $group_id);
            $crossref_fact->fetchDatas();
            if ($crossref_fact->getNbReferences() > 0) {
                echo '<b> ' . $GLOBALS['Language']->getText('cross_ref_fact_include', 'references') . '</b>';
                $crossref_fact->DisplayCrossRefs();
            }
            echo Codendi_HTMLPurifier::instance()->purify($row['job_url'], CODENDI_PURIFIER_BASIC_NOBR, $group_id);
        } else {
            echo '<span class="error">' . dgettext('tuleap-hudson', 'Error: Jenkins object not found.') . '</span>';
        }
    }

    public function build_number()
    {
        $request  = HTTPRequest::instance();
        $group_id = $request->get('group_id');
        if ($request->exist('build')) {
            $build_id = $request->get('build');
        } else {
            $build_id = $request->get('build_id');
        }
        $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
        if ($request->exist('job_id')) {
            $job_id = $request->get('job_id');
            $dar    = $job_dao->searchByJobID($job_id);
        } elseif ($request->exist('job')) {
            // used for references (build #MyJob/175 or job #myproject:MyJob/175 where 175 is the build number required)
            $job_name = $request->get('job');
            $dar      = $job_dao->searchByJobName($job_name, $group_id);
        } else {
            // used for references (build #175 where 175 is the build number required)
            // If no job or project is specified, we check if there is only one job associated to the current project and we assume it is this job.
            $dar = $job_dao->searchByGroupID($group_id);
            if ($dar->rowCount() != 1) {
                $dar = null;
            }
        }

        if ($dar && $dar->valid()) {
            $row           = $dar->current();
            $crossref_fact = new CrossReferenceFactory($row['name'] . '/' . $build_id, 'hudson_build', $group_id);
            $crossref_fact->fetchDatas();
            if ($crossref_fact->getNbReferences() > 0) {
                echo '<b> ' . $GLOBALS['Language']->getText('cross_ref_fact_include', 'references') . '</b>';
                $crossref_fact->DisplayCrossRefs();
            }
            echo Codendi_HTMLPurifier::instance()->purify(
                $row['job_url'] . '/' . $build_id . '/',
                CODENDI_PURIFIER_BASIC_NOBR,
                $group_id
            );
        } else {
            echo '<span class="error">' . dgettext('tuleap-hudson', 'Error: Jenkins object not found.') . '</span>';
        }
    }

    public function last_test_result()
    {
        $request  = HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $job_id   = $request->get('job_id');
        $user     = UserManager::instance()->getCurrentUser();

        $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
        $dar     = $job_dao->searchByJobID($job_id);
        if ($dar->valid()) {
            $row = $dar->current();
            echo Codendi_HTMLPurifier::instance()->purify(
                $row['job_url'] . '/lastBuild/testReport/',
                CODENDI_PURIFIER_BASIC_NOBR,
                $group_id
            );
        } else {
            echo '<span class="error">' . dgettext('tuleap-hudson', 'Error: Jenkins object not found.') . '</span>';
        }
    }

    public function test_trend()
    {
        $request  = HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $job_id   = $request->get('job_id');
        $user     = UserManager::instance()->getCurrentUser();

        $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
        $dar     = $job_dao->searchByJobID($job_id);
        if ($dar->valid()) {
            $row = $dar->current();
            echo Codendi_HTMLPurifier::instance()->purify(
                $row['job_url'] . '/test/?width=800&height=600&failureOnly=false',
                CODENDI_PURIFIER_BASIC_NOBR,
                $group_id
            );
        } else {
            echo '<span class="error">' . dgettext('tuleap-hudson', 'Error: Jenkins object not found.') . '</span>';
        }
    }

    public function editJob()
    {
        echo '<div class="continuous-integration-content">';
        $request  = HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $job_id   = $request->get('job_id');
        $user     = UserManager::instance()->getCurrentUser();
        if ($user->isMember($group_id, 'A')) {
            $project_manager = ProjectManager::instance();
            $project         = $project_manager->getProject($group_id);

            $em      = EventManager::instance();
            $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
            $dar     = $job_dao->searchByJobID($job_id);
            if ($dar->valid()) {
                $row = $dar->current();

                echo '<a href="/plugins/hudson/?group_id=' . Codendi_HTMLPurifier::instance()->purify(urlencode($group_id)) . '">' . dgettext('tuleap-hudson', 'Back to jobs list') . '</a>';

                echo '<h3>' . dgettext('tuleap-hudson', 'Edit job') . '</h3>';

                $services = [];
                $params   = ['group_id' => $group_id, 'job_id' => $job_id, 'services' => &$services];
                $em->processEvent('collect_ci_triggers', $params);

                $button = dgettext('tuleap-hudson', 'Update job');
                $this->displayForm(
                    $project,
                    $services,
                    'edit',
                    'update',
                    $button,
                    $job_id,
                    $row['job_url'],
                    $row['name'],
                    $row['use_svn_trigger'],
                    $row['token'],
                    $row['svn_paths']
                );
            }
        }
        echo '</div>';
    }
    // }}}

    public function _display_jobs_table($group_id, $services)
    {
        $request  = HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $purifier = Codendi_HTMLPurifier::instance();
        $user     = UserManager::instance()->getCurrentUser();
        $job_dao  = new PluginHudsonJobDao(CodendiDataAccess::instance());
        $dar      = $job_dao->searchByGroupID($group_id);

        if ($dar && $dar->valid()) {
            $project_manager = ProjectManager::instance();
            $project         = $project_manager->getProject($group_id);

            echo '<table id="jobs_table" class="table">';
            echo ' <thead><tr>';
            echo '  <th>' . $purifier->purify(dgettext('tuleap-hudson', 'Job')) . '</th>';
            echo '  <th>' . $purifier->purify(dgettext('tuleap-hudson', 'Last Success')) . '</th>';
            echo '  <th>' . $purifier->purify(dgettext('tuleap-hudson', 'Last Failure')) . '</th>';
            echo '  <th>' . $purifier->purify(dgettext('tuleap-hudson', 'RSS')) . '</th>';
            if ($project->usesSVN()) {
                echo '  <th>' . $purifier->purify(dgettext('tuleap-hudson', 'SVN trigger')) . '</th>';
            }
            if (! empty($services)) {
                foreach ($services as $service) {
                    echo '  <th>' . $purifier->purify($service['title']) . '</th>';
                }
            }
            if ($user->isMember($request->get('group_id'), 'A')) {
                echo '  <th>' . $purifier->purify(dgettext('tuleap-hudson', 'Actions')) . '</th>';
            }
            echo ' </tr></thead>';
            echo '<tbody>';
            $cpt                                   = 1;
            $minimal_job_factory                   = new MinimalHudsonJobFactory();
            $job_builder                           = new HudsonJobBuilder(
                HTTPFactoryBuilder::requestFactory(),
                HttpClientFactory::createAsyncClient()
            );
            $minimal_hudson_jobs                   = [];
            $hudson_jobs_complementary_information = [];

            foreach ($dar as $row) {
                $job_id = $row['job_id'];
                try {
                    $minimal_hudson_jobs[$job_id]                   = $minimal_job_factory->getMinimalHudsonJob($row['job_url'], $row['name']);
                    $hudson_jobs_complementary_information[$job_id] = [
                        'name'            => $row['name'],
                        'url'             => $row['job_url'],
                        'use_svn_trigger' => $row['use_svn_trigger'],
                    ];
                } catch (HudsonJobURLMalformedException $ex) {
                    // Managed when a new job is added
                }
            }

            $hudson_jobs_with_exception = $job_builder->getHudsonJobsWithException($minimal_hudson_jobs);
            $uri_sanitizer              = new URISanitizer(new Valid_HTTPURI());

            foreach ($hudson_jobs_with_exception as $job_id => $hudson_job_with_exception) {
                echo ' <tr>';

                try {
                    $job = $hudson_job_with_exception->getHudsonJob();

                    echo '<td>';
                    echo '<img src="' . $purifier->purify($job->getStatusIcon()) . '" alt="' . $purifier->purify($job->getStatus()) . '" title="' . $purifier->purify($job->getStatus()) . '" /> ';
                    echo '<a href="' . $purifier->purify($uri_sanitizer->sanitizeForHTMLAttribute($job->getUrl())) . '" title="' . $purifier->purify(sprintf(dgettext('tuleap-hudson', 'Show job %1$s'), $job->getName())) . '">' . $purifier->purify($job->getName()) . '</a>';
                    echo '</td>';
                    if ($job->getLastSuccessfulBuildNumber() !== 0) {
                        echo '  <td><a href="' . $purifier->purify($uri_sanitizer->sanitizeForHTMLAttribute($job->getLastSuccessfulBuildUrl())) . '" title="' . $purifier->purify(sprintf(dgettext('tuleap-hudson', 'Show build #%1$s of job %2$s'), $job->getLastSuccessfulBuildNumber(), $job->getName())) . '">' . $purifier->purify(dgettext('tuleap-hudson', 'build') . ' #' . $job->getLastSuccessfulBuildNumber()) . '</a></td>';
                    } else {
                        echo '  <td>&nbsp;</td>';
                    }
                    if ($job->getLastFailedBuildNumber() !== 0) {
                        echo '  <td><a href="' . $purifier->purify($uri_sanitizer->sanitizeForHTMLAttribute($job->getLastFailedBuildUrl())) . '" title="' . $purifier->purify(sprintf(dgettext('tuleap-hudson', 'Show build #%1$s of job %2$s'), $job->getLastFailedBuildNumber(), $job->getName())) . '">' . $purifier->purify(dgettext('tuleap-hudson', 'build') . ' #' . $job->getLastFailedBuildNumber()) . '</a></td>';
                    } else {
                        echo '  <td>&nbsp;</td>';
                    }
                    echo '  <td align="center"><a href="' . $purifier->purify($uri_sanitizer->sanitizeForHTMLAttribute($job->getUrl())) . '/rssAll"><img src="' . hudsonPlugin::ICONS_PATH . 'rss_feed.png" alt="' . $purifier->purify(sprintf(dgettext('tuleap-hudson', 'RSS feed of all builds for %1$s job'), $job->getName())) . '" title="' . $purifier->purify(sprintf(dgettext('tuleap-hudson', 'RSS feed of all builds for %1$s job'), $job->getName())) . '"></a></td>';

                    if ($project->usesSVN()) {
                        if ($hudson_jobs_complementary_information[$job_id]['use_svn_trigger'] == 1) {
                            echo '  <td align="center"><img src="' . $purifier->purify(hudsonPlugin::ICONS_PATH) . 'server_lightning.png" alt="' . dgettext('tuleap-hudson', 'SVN commit will trigger a build') . '" title="' . dgettext('tuleap-hudson', 'SVN commit will trigger a build') . '"></td>';
                        } else {
                            echo '  <td>&nbsp;</td>';
                        }
                    }
                    if (! empty($services)) {
                        foreach ($services as $service) {
                            if (isset($service['used'][$job_id]) && $service['used'][$job_id] == true) {
                                echo '  <td align="center"><img src="' . $purifier->purify(hudsonPlugin::ICONS_PATH) . 'server_lightning.png" alt="' . $purifier->purify($service['title']) . '" title="' . $purifier->purify($service['title']) . '"></td>';
                            } else {
                                echo '  <td>&nbsp;</td>';
                            }
                        }
                    }
                } catch (Exception $e) {
                    echo '  <td>';
                    echo '<img src="' . $purifier->purify(hudsonPlugin::ICONS_PATH) . 'link_error.png" alt="' . $purifier->purify($e->getMessage()) . '" title="' . $purifier->purify($e->getMessage()) . '" /> ';
                    echo '<a href="' . $purifier->purify($uri_sanitizer->sanitizeForHTMLAttribute($hudson_jobs_complementary_information[$job_id]['url'])) . '" title="' . $purifier->purify(sprintf(dgettext('tuleap-hudson', 'Show job %1$s'), $hudson_jobs_complementary_information[$job_id]['name'])) . '">';
                    echo $purifier->purify($hudson_jobs_complementary_information[$job_id]['name']);
                    echo '</a>';
                    echo '</td>';
                    $nb_columns = 3;
                    if ($project->usesSVN()) {
                        $nb_columns++;
                    }

                    foreach ($services as $service) {
                        $nb_columns++;
                    }
                    echo '  <td colspan="' . $nb_columns . '"><span class="error">' . $purifier->purify($e->getMessage()) . '</span></td>';
                }

                if ($user->isMember($request->get('group_id'), 'A')) {
                    echo '  <td>';
                    // edit job
                    echo '   <span class="job_action">';
                    echo '    <a href="?action=edit_job&group_id=' . $purifier->purify(urlencode($group_id)) . '&job_id=' . $purifier->purify(urlencode($job_id)) . '">' . $GLOBALS['HTML']->getimage(
                        'ic/edit.png',
                        ['alt' => $purifier->purify(dgettext('tuleap-hudson', 'Edit this job')),
                            'title' => $purifier->purify(dgettext('tuleap-hudson', 'Edit this job')),
                        ]
                    ) . '</a>';
                    echo '   </span>';
                    // delete job
                    echo '   <span class="job_action">';
                    echo '    <a href="?action=delete_job&group_id=' .  $purifier->purify(urlencode($group_id)) . '&job_id=' . $purifier->purify(urlencode($job_id)) . '" onclick="return confirm(';
                    echo "'" . $purifier->purify(sprintf(dgettext('tuleap-hudson', 'Are you sure you want to delete Job %1$s from project %2$s?'), $hudson_jobs_complementary_information[$job_id]['name'], $project->getUnixName())) . "'";
                    echo ');">' . $GLOBALS['HTML']->getimage(
                        'ic/cross.png',
                        ['alt' => dgettext('tuleap-hudson', 'Delete this job from this project'),
                            'title' => dgettext('tuleap-hudson', 'Delete this job from this project'),
                        ]
                    ) . '</a>';
                    echo '   </span>';
                    echo '  </td>';
                }

                echo ' </tr>';

                $cpt++;
            }
            echo '</table>';
        } else {
            echo '<p>' . $purifier->purify(dgettext('tuleap-hudson', 'No Jenkins jobs associated with this project. To add a job, select the link just below.')) . '</p>';
        }
    }

    public function _display_add_job_form($group_id, $services)
    {
        $project_manager = ProjectManager::instance();
        $project         = $project_manager->getProject($group_id);

        // function toggle_addurlform is in script plugins/hudson/www/hudson_tab.js
        echo '<input class="btn btn-primary" value="' . dgettext('tuleap-hudson', 'Add job') . '" type="submit" onclick="toggle_addurlform(); return false;">';
        echo ' ' . $this->_getHelp('hudson-service', true);
        echo '<div id="hudson_add_job">';
        $this->displayForm($project, $services, 'add', 'add', dgettext('tuleap-hudson', 'Submit'), null, null, null, null, null, '');
        echo '</div>';
        echo "<script>Element.toggle('hudson_add_job', 'slide');</script>";
    }

    private function displayForm(
        $project,
        $services,
        $add_or_edit,
        $action,
        $button,
        $job_id,
        $job_url,
        $name,
        $use_svn_trigger,
        $token,
        $svn_paths,
    ) {
        $purifier = Codendi_HTMLPurifier::instance();

        echo '  <form class="form-horizontal">
                    <input type="hidden" name="group_id" value="' . $purifier->purify($project->getId()) . '" />
                    <input type="hidden" name="job_id" value="' . $purifier->purify($job_id) . '" />
                    <input type="hidden" name="action" value="' . $purifier->purify($action) . '_job" />
                    <div class="control-group">
                        <label class="control-label" for="hudson_job_url">' . $purifier->purify(dgettext('tuleap-hudson', 'Job URL:')) . '</label>
                        <div class="controls">
                            <input id="hudson_job_url" required name="hudson_job_url" type="text" size="64" value="' . $purifier->purify($job_url) . '" />
                            <span class="help help-inline">' . $purifier->purify(dgettext('tuleap-hudson', 'eg: http://myCIserver/jenkins/job/myJob or http://myCIserver/jenkins/job/myJob/buildWithParameters?param1=value1 for parameterized jobs')) . '</span>
                        </div>
                    </div>';
        if ($name !== null) {
            echo '  <div class="control-group">
                        <label class="control-label" for="hudson_job_name">' . $purifier->purify(dgettext('tuleap-hudson', 'Job name:')) . '</label>
                        <div class="controls">
                            <input id="hudson_job_name" name="hudson_job_name" type="text" size="64" value="' . $purifier->purify($name) . '" />
                            <span class="help help-inline">' . $purifier->purify(sprintf(dgettext('tuleap-hudson', 'Name (with no space) used to make a reference to this job. Eg: job #%1$s'), $name)) . '</span>
                        </div>
                    </div>';
        }
        if ($project->usesSVN() || ! empty($services)) {
            echo '  <div class="control-group">
                        <label class="control-label" for="hudson_job_url">' . dgettext('tuleap-hudson', 'Trigger a build after commits:') . '</label>
                            <div class="controls">';
            if ($project->usesSVN()) {
                $checked = '';
                if ($use_svn_trigger) {
                    $checked = ' checked="checked" ';
                }
                echo '<label class="checkbox">
                        <input id="hudson_use_svn_trigger" name="hudson_use_svn_trigger" type="checkbox" ' . $checked . '/>
                        ' . $purifier->purify(dgettext('tuleap-hudson', 'SVN')) . '
                      </label>
                      <div id="hudson_svn_paths">
                        <label for="hudson_svn_paths_textarea">' . $purifier->purify(dgettext('tuleap-hudson', 'Only when commit occurs on following paths:')) . '</label>
                        <textarea
                          id="hudson_svn_paths_textarea"
                          name="hudson_svn_paths"
                          placeholder="' . $purifier->purify(dgettext('tuleap-hudson', 'One path per line...')) . '"
                        >' . $purifier->purify($svn_paths) . '</textarea>
                        <p class="help">' . $purifier->purify(dgettext('tuleap-hudson', 'If empty, every commits will trigger a build.')) . '</p>
                      </div>
                    ';
            }
            foreach ($services as $service) {
                echo $service[$add_or_edit . '_form'];
            }
            echo '          <label class="hudson_token_label">
                                ' . dgettext('tuleap-hudson', 'with (optional) token:') . '
                                <input id="hudson_trigger_token" name="hudson_trigger_token" type="text" size="32" value="' . $purifier->purify($token) . '" />
                            </label>
                        </div>
                  </div>';
        }
        echo '    <div class="control-group">
                    <div class="controls">
                        <input type="submit" class="btn btn-primary" value="' . $purifier->purify($button) . '" />
                    </div>
                  </div>
                </form>';
    }
}
