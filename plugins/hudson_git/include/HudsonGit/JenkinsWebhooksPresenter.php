<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use Tuleap\Git\Webhook\WebhookPresenter;
use Tuleap\Git\Webhook\WebhookLogPresenter;
use Codendi_HTMLPurifier;
use GitRepository;
use CSRFSynchronizerToken;

class JenkinsWebhookPresenter extends WebhookPresenter
{
    public function __construct(GitRepository $repository, $url, array $hooklogs, CSRFSynchronizerToken $csrf)
    {
        $use_default_edit_modal = false;
        parent::__construct($repository, 'jenkins', $url, array(), $csrf, $use_default_edit_modal);

        $this->remove_form_action   = '/plugins/hudson_git/?group_id='. (int)$repository->getProjectId();

        $this->remove_webhook_desc   = $GLOBALS['Language']->getText('plugin_hudson_git', 'remove_jenkins_desc');
        $this->modal_logs_time_label = $GLOBALS['Language']->getText('plugin_hudson_git', 'label_push_date');
        $this->modal_logs_info_label = $GLOBALS['Language']->getText('plugin_hudson_git', 'label_triggered');
        $this->empty_logs            = $GLOBALS['Language']->getText('plugin_hudson_git', 'empty_jobs');

        $this->last_push_info = $GLOBALS['Language']->getText(
            'plugin_hudson_git',
            'n_jobs_triggered',
            $this->countNbJobsTriggeredOnLastPush($hooklogs)
        );

        $this->generateHooklogs($hooklogs);
    }

    private function generateHooklogs(array $hooklogs)
    {
        $hp = Codendi_HTMLPurifier::instance();
        foreach ($hooklogs as $log) {
            $purified_information = '';
            foreach ($log->getJobUrlList() as $triggered_job_url) {
                $purfied_job_url = $hp->purify($triggered_job_url);
                $purified_information .= '<a href="'. $purfied_job_url .'">'. $purfied_job_url .'</a><br>';
            }
            $this->hooklogs[] = new WebhookLogPresenter($log->getFormattedPushDate(), $purified_information);
        }
    }

    public function countNbJobsTriggeredOnLastPush($hooklogs)
    {
        if (count($hooklogs) > 0) {
            return count($hooklogs[0]->getJobUrlList());
        }

        return 0;
    }
}
