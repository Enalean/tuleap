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

namespace Tuleap\HudsonGit;

use Tuleap\Git\Webhook\GenericWebhookPresenter;
use Tuleap\Git\Webhook\WebhookLogPresenter;
use Codendi_HTMLPurifier;
use GitRepository;
use CSRFSynchronizerToken;
use Tuleap\HudsonGit\Log\Log;

class JenkinsWebhookPresenter extends GenericWebhookPresenter
{
    public function __construct(GitRepository $repository, $url, array $hooklogs, CSRFSynchronizerToken $csrf)
    {
        $use_default_edit_modal = false;
        parent::__construct($repository, 'jenkins', $url, array(), $csrf, $use_default_edit_modal);

        $this->remove_form_action   = '/plugins/hudson_git/?group_id=' . (int) $repository->getProjectId();

        $this->remove_webhook_desc   = $GLOBALS['Language']->getText('plugin_hudson_git', 'remove_jenkins_desc');
        $this->modal_logs_time_label = $GLOBALS['Language']->getText('plugin_hudson_git', 'label_push_date');
        $this->modal_logs_info_label = dgettext('tuleap-hudson_git', 'Logs');
        $this->empty_logs            = $GLOBALS['Language']->getText('plugin_hudson_git', 'empty_jobs');

        $this->purified_last_push_info = '<span class="text-info">' . $GLOBALS['Language']->getText(
            'plugin_hudson_git',
            'n_jobs_triggered',
            $this->countNumberOfPollingJobsTriggeredOnLastPush($hooklogs)
        ) . '</span>';

        $this->generateHooklogs($hooklogs);
    }

    /**
     * @param Log[] $hooklogs
     */
    private function generateHooklogs(array $hooklogs): void
    {
        $hp = Codendi_HTMLPurifier::instance();
        foreach ($hooklogs as $log) {
            $purified_information = '';
            $job_list = $log->getJobUrlList();
            if (count($job_list) > 0) {
                $purified_information .= '<div class="hook-log-triggered-jobs">';
                $purified_information .= '<h4>' . dgettext("tuleap-hudson_git", "Git plugin triggered jobs:") . '</h4>';
                foreach ($job_list as $triggered_job_url) {
                    $purfied_job_url = $hp->purify($triggered_job_url);
                    $purified_information .= '<a href="' . $purfied_job_url . '">' . $purfied_job_url . '</a><br>';
                }
                $purified_information .= '</div>';
            }

            if ($log->getStatusCode() !== null) {
                $purified_information .= '<div class="hook-log-branch-source-status">';
                $purified_information .= '<h4>' . dgettext("tuleap-hudson_git", "Branch source plugin:") . '</h4>';
                $purified_information .= $log->getStatusCode();
                $purified_information .= '</div>';
            }

            $this->hooklogs[] = new WebhookLogPresenter($log->getFormattedPushDate(), $purified_information);
        }
    }

    /**
     * @param Log[] $hooklogs
     */
    private function countNumberOfPollingJobsTriggeredOnLastPush(array $hooklogs): int
    {
        if (count($hooklogs) > 0) {
            return count($hooklogs[0]->getJobUrlList());
        }

        return 0;
    }
}
