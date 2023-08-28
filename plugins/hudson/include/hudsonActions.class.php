<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

/**
 * hudsonActions
 */
class hudsonActions extends Actions
{
    public function __construct($controler)
    {
        parent::__construct($controler);

        $this->svn_paths_updater = new SVNPathsUpdater();
    }

    public function addJob()
    {
        $request  = HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $job_url  = $request->get('hudson_job_url');
        try {
            $minimal_job_factory = new MinimalHudsonJobFactory();
            $job_builder         = new HudsonJobBuilder(HTTPFactoryBuilder::requestFactory(), HttpClientFactory::createAsyncClient());
            $job                 = $job_builder->getHudsonJob(
                $minimal_job_factory->getMinimalHudsonJob($job_url, '')
            );

            $use_svn_trigger = ($request->get('hudson_use_svn_trigger') === 'on');
            $token           = $request->get('hudson_trigger_token');
            $svn_paths       = $this->svn_paths_updater->transformContent($request->get('hudson_svn_paths'));

            $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
            $jobId   = $job_dao->createHudsonJob(
                $group_id,
                $job_url,
                $job->getName(),
                $use_svn_trigger,
                $token,
                $svn_paths
            );

            if (! $jobId) {
                $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-hudson', 'Unable to add Jenkins job.'));
            } else {
                $em     = EventManager::instance();
                $params = ['job_id' => $jobId, 'request' => $request];
                $em->processEvent('save_ci_triggers', $params);
                $GLOBALS['Response']->addFeedback('info', dgettext('tuleap-hudson', 'Jenkins job added.'));
                $GLOBALS['Response']->redirect('/plugins/hudson/?group_id=' . intval($group_id));
            }
        } catch (Exception $e) {
            $GLOBALS['Response']->addFeedback('error', $e->getMessage());
        }
    }

    public function updateJob()
    {
        $request      = HTTPRequest::instance();
        $job_id       = $request->get('job_id');
        $new_job_url  = $request->get('hudson_job_url');
        $new_job_name = $request->get('hudson_job_name');

        if (strpos($new_job_name, " ") !== false) {
            $new_job_name = str_replace(" ", "_", $new_job_name);
            $GLOBALS['Response']->addFeedback('warning', dgettext('tuleap-hudson', 'Spaces are not allowed in job name. They were replaced by "_".'));
        }

        $new_use_svn_trigger = ($request->get('hudson_use_svn_trigger') === 'on');
        $new_token           = $request->get('hudson_trigger_token');
        $svn_paths           = $this->svn_paths_updater->transformContent($request->get('hudson_svn_paths'));
        $job_dao             = new PluginHudsonJobDao(CodendiDataAccess::instance());

        if (
            ! $job_dao->updateHudsonJob(
                $job_id,
                $new_job_url,
                $new_job_name,
                $new_use_svn_trigger,
                $new_token,
                $svn_paths
            )
        ) {
            $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-hudson', 'Unable to update Jenkins job'));
        } else {
            $GLOBALS['Response']->addFeedback('info', dgettext('tuleap-hudson', 'Jenkins job updated.'));
            $em     = EventManager::instance();
            $params = ['request' => $request];
            $em->processEvent('update_ci_triggers', $params);
        }
    }

    public function deleteJob()
    {
        $request = HTTPRequest::instance();
        $job_id  = $request->get('job_id');
        $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
        if (! $job_dao->deleteHudsonJob($job_id)) {
            $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-hudson', 'Unable to delete Jenkins job'));
        } else {
            $GLOBALS['Response']->addFeedback('info', dgettext('tuleap-hudson', 'Jenkins job deleted.'));
            $em     = EventManager::instance();
            $params = ['job_id' => $job_id];
            $em->processEvent('delete_ci_triggers', $params);
        }
    }
}
