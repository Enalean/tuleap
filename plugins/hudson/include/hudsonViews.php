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
use Tuleap\Hudson\CSRFSynchronizerTokenProvider;
use Tuleap\Hudson\HudsonJobBuilder;
use Tuleap\Sanitizer\URISanitizer;

class hudsonViews extends Views // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
{
    private const string EDIT = 'edit';
    private const string ADD  = 'add';

    private readonly CSRFSynchronizerTokenProvider $csrf_token_provider;

    public function __construct(&$controler, $view = null)
    {
        $this->View($controler, $view);
        $this->csrf_token_provider = new CSRFSynchronizerTokenProvider();
    }

    #[\Override]
    public function header()
    {
        $request = HTTPRequest::instance();
        $GLOBALS['HTML']->header(
            \Tuleap\Layout\HeaderConfigurationBuilder::get($this->getTitle())
                ->inProject($request->getProject(), 'hudson')
                ->withBodyClass(['continuous-integration-body'])
                ->build()
        );
        echo '<h1 class="continuous-integration-title">' . $this->getTitle() . '</h1>';
    }

    private function getTitle(): string
    {
        return dgettext('tuleap-hudson', 'Continuous Integration');
    }

    #[\Override]
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
        $purifier = Codendi_HTMLPurifier::instance();

        $title = dgettext('tuleap-hudson', 'Jobs');
        echo <<<EOS
        <section class="tlp-pane">
            <div class="tlp-pane-container">
                <div class="tlp-pane-header">
                    <h1 class="tlp-pane-title">
                        <i class="tlp-pane-title-icon fa-solid fa-list" aria-hidden="true"></i>
                        {$purifier->purify($title)}
                    </h1>
                </div>
                <section class="tlp-pane-section">
        EOS;

        $em->processEvent('collect_ci_triggers', $params);
        if ($user->isMember($request->get('group_id'), 'A')) {
            $this->displayAddJobForm($group_id, $services);
        }
        $this->displayJobsTable($group_id, $services);
        echo <<<EOS
                </section>
            </div>
        </section>
        EOS;
        echo '</div>';
    }

    public function job_details(): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
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

        $purifier = Codendi_HTMLPurifier::instance();

        echo '<div class="continuous-integration-content">';
        if ($dar->valid()) {
            $title = dgettext('tuleap-hudson', 'Job details');
            echo <<<EOS
            <section class="tlp-pane">
                <div class="tlp-pane-container">
                    <div class="tlp-pane-header">
                        <h1 class="tlp-pane-title">
                            <i class="tlp-pane-title-icon fa-solid fa-list" aria-hidden="true"></i>
                            {$purifier->purify($title)}
                        </h1>
                    </div>
                    <section class="tlp-pane-section">
            EOS;
            $row = $dar->current();

            echo '<p>';
            echo Codendi_HTMLPurifier::instance()->purify($row['job_url'], CODENDI_PURIFIER_BASIC_NOBR, $group_id);
            echo '</p>';

            $crossref_fact = new CrossReferenceFactory($row['name'], 'hudson_job', $group_id);
            $crossref_fact->fetchDatas();
            if ($crossref_fact->getNbReferences() > 0) {
                echo '<b> ' . $GLOBALS['Language']->getText('cross_ref_fact_include', 'references') . '</b>';
                $crossref_fact->DisplayCrossRefs();
            }

            echo <<<EOS
                    </section>
                </div>
            </section>
            EOS;
        } else {
            echo '<div class="tlp-alert-danger">' . dgettext('tuleap-hudson', 'Error: Jenkins object not found.') . '</div>';
        }
        echo '</div>';
    }

    public function build_number(): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
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

        $purifier = Codendi_HTMLPurifier::instance();

        echo '<div class="continuous-integration-content">';
        if ($dar && $dar->valid()) {
            $title = dgettext('tuleap-hudson', 'Build details');
            echo <<<EOS
            <section class="tlp-pane">
                <div class="tlp-pane-container">
                    <div class="tlp-pane-header">
                        <h1 class="tlp-pane-title">
                            <i class="tlp-pane-title-icon fa-solid fa-list" aria-hidden="true"></i>
                            {$purifier->purify($title)}
                        </h1>
                    </div>
                    <section class="tlp-pane-section">
            EOS;

            $row = $dar->current();

            echo '<p>';
            echo Codendi_HTMLPurifier::instance()->purify(
                $row['job_url'] . '/' . $build_id . '/',
                CODENDI_PURIFIER_BASIC_NOBR,
                $group_id
            );
            echo '</p>';

            $crossref_fact = new CrossReferenceFactory($row['name'] . '/' . $build_id, 'hudson_build', $group_id);
            $crossref_fact->fetchDatas();
            if ($crossref_fact->getNbReferences() > 0) {
                echo '<b> ' . $GLOBALS['Language']->getText('cross_ref_fact_include', 'references') . '</b>';
                $crossref_fact->DisplayCrossRefs();
            }
            echo <<<EOS
                    </section>
                </div>
            </section>
            EOS;
        } else {
            echo '<div class="tlp-alert-danger">' . dgettext('tuleap-hudson', 'Error: Jenkins object not found.') . '</div>';
        }
        echo '</div>';
    }

    public function last_test_result(): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $request  = HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $job_id   = $request->get('job_id');
        $purifier = Codendi_HTMLPurifier::instance();

        $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
        $dar     = $job_dao->searchByJobID($job_id);

        echo '<div class="continuous-integration-content">';
        if ($dar->valid()) {
            $title = dgettext('tuleap-hudson', 'Latest test result');
            echo <<<EOS
            <section class="tlp-pane">
                <div class="tlp-pane-container">
                    <div class="tlp-pane-header">
                        <h1 class="tlp-pane-title">
                            <i class="tlp-pane-title-icon fa-solid fa-list" aria-hidden="true"></i>
                            {$purifier->purify($title)}
                        </h1>
                    </div>
                    <section class="tlp-pane-section">
            EOS;
            $row = $dar->current();
            echo Codendi_HTMLPurifier::instance()->purify(
                $row['job_url'] . '/lastBuild/testReport/',
                CODENDI_PURIFIER_BASIC_NOBR,
                $group_id
            );
            echo <<<EOS
                    </section>
                </div>
            </section>
            EOS;
        } else {
            echo '<div class="tlp-alert-danger">' . dgettext('tuleap-hudson', 'Error: Jenkins object not found.') . '</div>';
        }
        echo '</div>';
    }

    public function test_trend(): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $request  = HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $job_id   = $request->get('job_id');
        $purifier = Codendi_HTMLPurifier::instance();

        $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
        $dar     = $job_dao->searchByJobID($job_id);
        echo '<div class="continuous-integration-content">';
        if ($dar->valid()) {
            $title = dgettext('tuleap-hudson', 'Test trend');
            echo <<<EOS
            <section class="tlp-pane">
                <div class="tlp-pane-container">
                    <div class="tlp-pane-header">
                        <h1 class="tlp-pane-title">
                            <i class="tlp-pane-title-icon fa-solid fa-list" aria-hidden="true"></i>
                            {$purifier->purify($title)}
                        </h1>
                    </div>
                    <section class="tlp-pane-section">
            EOS;
            $row = $dar->current();
            echo Codendi_HTMLPurifier::instance()->purify(
                $row['job_url'] . '/test/?width=800&height=600&failureOnly=false',
                CODENDI_PURIFIER_BASIC_NOBR,
                $group_id
            );
            echo <<<EOS
                    </section>
                </div>
            </section>
            EOS;
        } else {
            echo '<div class="tlp-alert-danger">' . dgettext('tuleap-hudson', 'Error: Jenkins object not found.') . '</div>';
        }
        echo '</div>';
    }

    private function displayJobsTable($group_id, $services): void
    {
        $request  = HTTPRequest::instance();
        $purifier = Codendi_HTMLPurifier::instance();
        $user     = UserManager::instance()->getCurrentUser();
        $job_dao  = new PluginHudsonJobDao(CodendiDataAccess::instance());
        $dar      = $job_dao->searchByGroupID($group_id);

        if ($dar && $dar->valid()) {
            $project_manager = ProjectManager::instance();
            $project         = $project_manager->getProject($group_id);

            echo '<table id="jobs_table" class="tlp-table">';
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
                echo '  <th></th>';
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

            $rows = [];
            foreach ($dar as $row) {
                $job_id        = $row['job_id'];
                $rows[$job_id] = $row;
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
                    echo '  <td align="center">
                        <a href="' . $purifier->purify($uri_sanitizer->sanitizeForHTMLAttribute($job->getUrl())) . '/rssAll">
                            <i class="fa-solid fa-square-rss"
                                role="img"
                                title="' . $purifier->purify(sprintf(dgettext('tuleap-hudson', 'RSS feed of all builds for %1$s job'), $job->getName())) . '"
                            ></i>
                        </a>
                    </td>';

                    if ($project->usesSVN()) {
                        echo '<td>';
                        if ($hudson_jobs_complementary_information[$job_id]['use_svn_trigger'] == 1) {
                            echo '<i class="fa-solid fa-check"
                                role="img"
                                title="' . $purifier->purify(dgettext('tuleap-hudson', 'SVN commit will trigger a build')) . '"
                            ></i>';
                        }
                        echo '</td>';
                    }
                    if (! empty($services)) {
                        foreach ($services as $service) {
                            echo '<td>';
                            if (isset($service['used'][$job_id]) && $service['used'][$job_id] == true) {
                                echo '<i class="fa-solid fa-check"
                                    role="img"
                                    title="' . $purifier->purify($service['title']) . '"
                                ></i>';
                            }
                            echo '</td>';
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
                    echo '  <td colspan="' . $nb_columns . '"><div class="tlp-alert-danger">' . $purifier->purify($e->getMessage()) . '</div></td>';
                }

                if ($user->isMember($request->get('group_id'), 'A')) {
                    $job_services = [];
                    $params       = ['group_id' => $group_id, 'job_id' => $job_id, 'services' => &$job_services];
                    EventManager::instance()->processEvent('collect_ci_triggers', $params);

                    echo '  <td class="tlp-table-cell-actions">';

                    // edit job
                    $edit_modal_id = 'continuous-integration-edit-job-modal-' . $job_id;
                    $this->displayForm(
                        $edit_modal_id,
                        $project,
                        $job_services,
                        self::EDIT,
                        'update',
                        dgettext('tuleap-hudson', 'Update job'),
                        $job_id,
                        $rows[$job_id]['job_url'],
                        $rows[$job_id]['name'],
                        $rows[$job_id]['use_svn_trigger'],
                        $rows[$job_id]['token'],
                        $rows[$job_id]['svn_paths']
                    );
                    echo '<button
                        type="button"
                        class="tlp-table-cell-actions-button tlp-button-primary tlp-button-outline tlp-button-small continuous-integration-modal-button"
                        data-target-modal-id="' . $purifier->purify($edit_modal_id) . '"
                    >';
                    echo '<i class="fa-solid fa-pencil tlp-button-icon" aria-hidden="true"></i>';
                    echo $purifier->purify(_('Edit'));
                    echo '</button>';

                    // delete job
                    $delete_modal_id = 'continuous-integration-delete-job-modal-' . $job_id;
                    $this->displayDeleteModal($delete_modal_id, $job_id, $hudson_jobs_complementary_information[$job_id]['name'], $project);
                    echo '<button
                        type="button"
                        class="tlp-table-cell-actions-button tlp-button-danger tlp-button-outline tlp-button-small continuous-integration-modal-button"
                        data-target-modal-id="' . $purifier->purify($delete_modal_id) . '"
                    >';
                    echo '<i class="fa-regular fa-trash-can tlp-button-icon" aria-hidden="true"></i>';
                    echo $purifier->purify(_('Delete'));
                    echo '</button>';
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

    private function displayAddJobForm($group_id, $services): void
    {
        $project_manager = ProjectManager::instance();
        $project         = $project_manager->getProject($group_id);
        $purifier        = Codendi_HTMLPurifier::instance();

        $add = dgettext('tuleap-hudson', 'Add job');
        echo <<<EOS
            <div class="tlp-table-actions">
                <button type="button"
                    class="tlp-button-primary tlp-table-actions-element continuous-integration-modal-button"
                    data-target-modal-id="continuous-integration-add-job-modal"
                >
                    <i class="tlp-button-icon fa-solid fa-plus" aria-hidden="true"></i>
                    {$purifier->purify($add)}
                </button>
            EOS;
        $this->displayForm('continuous-integration-add-job-modal', $project, $services, self::ADD, 'add', dgettext('tuleap-hudson', 'Submit'), null, null, null, null, null, '');

        echo '</div>';
    }

    private function displayForm(
        string $modal_id,
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
    ): void {
        $purifier = Codendi_HTMLPurifier::instance();

        $title = $add_or_edit === self::ADD ? dgettext('tuleap-hudson', 'Add job') : dgettext('tuleap-hudson', 'Update job');
        $close = _('Close');
        echo <<<EOS
        <form class="tlp-modal" id="{$purifier->purify($modal_id)}" role="dialog" aria-labelledby="{$purifier->purify($modal_id)}-title">
            <div class="tlp-modal-header">
                <h1 class="tlp-modal-title" id="{$purifier->purify($modal_id)}-title">{$purifier->purify($title)}</h1>
                <button class="tlp-modal-close" type="button" data-dismiss="modal" aria-label="{$purifier->purify($close)}">
                    <i class="fa-solid fa-xmark tlp-modal-close-icon" aria-hidden="true"></i>
                </button>
            </div>
            <div class="tlp-modal-body">
        EOS;

        $csrf = $this->csrf_token_provider->getCSRF($project);

        echo '      <input type="hidden" name="' . $purifier->purify($csrf->getTokenName()) . '" value="' . $purifier->purify($csrf->getToken()) . '">
                    <input type="hidden" name="group_id" value="' . $purifier->purify($project->getId()) . '" />
                    <input type="hidden" name="job_id" value="' . $purifier->purify($job_id) . '" />
                    <input type="hidden" name="action" value="' . $purifier->purify($action) . '_job" />
                    <div class="tlp-form-element">
                        <label class="tlp-label" for="hudson_job_url">
                            ' . $purifier->purify(dgettext('tuleap-hudson', 'Job URL')) . '
                            <i class="fa-solid fa-asterisk" aria-hidden="true"></i>
                        </label>
                        <input type="text"
                            id="hudson_job_url"
                            name="hudson_job_url"
                            class="tlp-input"
                            placeholder="https://"
                            required
                            value="' . $purifier->purify($job_url) . '"
                        >
                        <p class="tlp-text-info">
                            ' . $purifier->purify(dgettext('tuleap-hudson', 'eg: http://myCIserver/jenkins/job/myJob or http://myCIserver/jenkins/job/myJob/buildWithParameters?param1=value1 for parameterized jobs')) . '
                        </p>
                    </div>';
        if ($name !== null) {
            echo '<div class="tlp-form-element">
                <label class="tlp-label" for="hudson_job_name">
                    ' . $purifier->purify(dgettext('tuleap-hudson', 'Job name')) . '
                </label>
                <input type="text" id="hudson_job_name" name="hudson_job_name" class="tlp-input" value="' . $purifier->purify($name) . '">
                <p class="tlp-text-info">
                    ' . $purifier->purify(sprintf(dgettext('tuleap-hudson', 'Name (with no space) used to make a reference to this job. Eg: job #%1$s'), $name)) . '
                </p>
            </div>';
        }
        if ($project->usesSVN() || ! empty($services)) {
            echo '<div class="tlp-form-element">
                <label class="tlp-label">' . dgettext('tuleap-hudson', 'Trigger a build after commits') . '</label>';
            if (! $project->usesSVN()) {
                $checked = '';
                if ($use_svn_trigger) {
                    $checked = ' checked="checked" ';
                }
                echo '<label class="tlp-label tlp-checkbox continuous-integration-trigger-option">
                        <input id="hudson_use_svn_trigger" name="hudson_use_svn_trigger" class="continuous-integration-trigger-option-checkbox" type="checkbox" ' . $checked . '/>
                        ' . $purifier->purify(dgettext('tuleap-hudson', 'SVN')) . '
                      </label>
                      <blockquote class="continuous-integration-trigger-option-details">
                        <div class="tlp-form-element">
                            <label class="tlp-label" for="hudson_svn_paths_textarea">' . $purifier->purify(dgettext('tuleap-hudson', 'Only when commit occurs on following paths')) . '</label>
                            <textarea
                              id="hudson_svn_paths_textarea"
                              class="tlp-textarea"
                              name="hudson_svn_paths"
                              placeholder="' . $purifier->purify(dgettext('tuleap-hudson', 'One path per line...')) . '"
                            >' . $purifier->purify($svn_paths) . '</textarea>
                            <p class="tlp-text-info">' . $purifier->purify(dgettext('tuleap-hudson', 'If empty, every commits will trigger a build.')) . '</p>
                      </blockquote>
                    ';
            }
            foreach ($services as $service) {
                echo $service[$add_or_edit . '_form'];
            }
            echo '</div>';

            echo '<div class="tlp-form-element">
                <label class="tlp-label" for="hudson_trigger_token">
                    ' . $purifier->purify(dgettext('tuleap-hudson', 'with (optional) token')) . '
                </label>
                <input type="text" id="hudson_trigger_token" name="hudson_trigger_token" class="tlp-input" value="' . $purifier->purify($token) . '">
            </div>';
        }
        echo '</div>
            <div class="tlp-modal-footer">
                <button type="button" data-dismiss="modal" class="tlp-button-primary tlp-button-outline tlp-modal-action">
                ' . _('Cancel') . '
                </button>
                <button type="submit" class="tlp-button-primary tlp-modal-action">' . $purifier->purify($button) . '</button>
            </div>
        </form>';
    }

    private function displayDeleteModal(string $delete_modal_id, int $job_id, string $name, Project $project): void
    {
        TemplateRendererFactory::build()->getRenderer(__DIR__)
            ->renderToPage('delete-modal', [
                'delete_modal_id' => $delete_modal_id,
                'job_id' => $job_id,
                'name' => $name,
                'project_name' => $project->getUnixName(),
                'project_id' => $project->getID(),
                'csrf_token' => $this->csrf_token_provider->getCSRF($project),
            ]);
    }
}
