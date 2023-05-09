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
use Tuleap\Sanitizer\URISanitizer;

final class JenkinsWebhookPresenter extends GenericWebhookPresenter
{
    public function __construct(
        GitRepository $repository,
        string $url,
        array $hooklogs,
        CSRFSynchronizerToken $csrf,
        private readonly Codendi_HTMLPurifier $html_purifier,
        private readonly URISanitizer $uri_sanitizer,
    ) {
        $use_default_edit_modal = false;
        parent::__construct($repository, 'jenkins', $url, [], $csrf, $use_default_edit_modal);

        $this->remove_form_action = '/plugins/hudson_git/?group_id=' . (int) $repository->getProjectId();

        $this->remove_webhook_desc   = dgettext('tuleap-hudson_git', 'You are about to remove the Jenkins server. Please confirm your action.');
        $this->modal_logs_time_label = dgettext('tuleap-hudson_git', 'Push date');
        $this->modal_logs_info_label = dgettext('tuleap-hudson_git', 'Logs');
        $this->empty_logs            = dgettext('tuleap-hudson_git', 'No triggered jobs');

        $this->purified_last_push_info = '<span class="text-info">' . sprintf(dgettext('tuleap-hudson_git', '%1$s jobs triggered'), $this->countNumberOfPollingJobsTriggeredOnLastPush($hooklogs)) . '</span>';

        $this->generateHooklogs($hooklogs);
    }

    /**
     * @param Log[] $hooklogs
     */
    private function generateHooklogs(array $hooklogs): void
    {
        foreach ($hooklogs as $log) {
            $purified_information = '';
            $job_list             = $log->getJobUrlList();
            if (count($job_list) > 0) {
                $purified_information .= '<div class="hook-log-triggered-jobs">';
                $purified_information .= '<h4>' . dgettext("tuleap-hudson_git", "Git plugin triggered jobs:") . '</h4>';
                foreach ($job_list as $triggered_job_url) {
                    $sanitized_job_url = $this->uri_sanitizer->sanitizeForHTMLAttribute($triggered_job_url);
                    if ($sanitized_job_url !== '' && $triggered_job_url !== '') {
                        $purified_information .= '<a href="' . $this->html_purifier->purify($sanitized_job_url) . '">' . $this->html_purifier->purify($triggered_job_url) . '</a><br>';
                    } else {
                        $purified_information .= $this->html_purifier->purify($triggered_job_url);
                    }
                }
                $purified_information .= '</div>';
            }

            if ($log->getStatusCode() !== null) {
                $purified_information .= '<div class="hook-log-branch-source-status">';
                $purified_information .= '<h4>' . dgettext("tuleap-hudson_git", "Branch source plugin:") . '</h4>';
                $purified_information .= $this->html_purifier->purify((string) $log->getStatusCode());
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
