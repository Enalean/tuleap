<?php
/**
 * Copyright (c) Enalean, 2015 — 2018. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\Config;

use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\Tracker\Admin\ArtifactDeletion\ArtifactsDeletionConfigController;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfigController;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureConfigController;
use CSRFSynchronizerToken;
use Response;
use PFUser;
use Feedback;
use Tuleap\Tracker\Report\TrackerReportConfigController;

class ConfigController implements DispatchableWithRequestNoAuthz, DispatchableWithBurningParrot
{

    /** @var NatureConfigController */
    private $nature_controller;

    /** @var MailGatewayConfigController */
    private $mailgateway_controller;

    /** @var CSRFSynchronizerToken */
    private $csrf;

    /**
     * @var TrackerReportConfigController
     */
    private $report_config_controller;

    /**
     * @var ArtifactsDeletionConfigController
     */
    private $deletion_controller;

    public function __construct(
        CSRFSynchronizerToken $csrf,
        MailGatewayConfigController $mailgateway_controller,
        NatureConfigController $nature_controller,
        TrackerReportConfigController $report_config_controller,
        ArtifactsDeletionConfigController $deletion_controller
    ) {
        $this->csrf                     = $csrf;
        $this->mailgateway_controller   = $mailgateway_controller;
        $this->nature_controller        = $nature_controller;
        $this->report_config_controller = $report_config_controller;
        $this->deletion_controller      = $deletion_controller;
    }

    public function process(\HTTPRequest $request, BaseLayout $response, array $variables)
    {
        $user = $request->getCurrentUser();
        $this->checkUserIsSiteadmin($user, $response);

        switch ($request->get('action')) {
            case 'create-nature':
                $this->csrf->check();
                $this->nature_controller->createNature($request, $response);
                break;
            case 'edit-nature':
                $this->csrf->check();
                $this->nature_controller->editNature($request, $response);
                break;
            case 'delete-nature':
                $this->csrf->check();
                $this->nature_controller->deleteNature($request, $response);
                break;
            case 'restrict-natures':
                $this->csrf->check();
                if ($request->exist('allow-project')) {
                    $this->nature_controller->allowProject($request, $response);
                } elseif ($request->exist('revoke-project')) {
                    $this->nature_controller->revokeProject($request, $response);
                } else {
                    $this->nature_controller->index($this->csrf, $response);
                }
                break;
            case 'natures':
                $this->nature_controller->index($this->csrf, $response);
                break;
            case 'update-emailgateway':
                $this->csrf->check();
                $this->mailgateway_controller->update($request, $response);
                break;
            case 'report-config':
                $this->report_config_controller->display($this->csrf);
                break;
            case 'update-report-config':
                $this->csrf->check();
                $this->report_config_controller->update($request, $response);
                break;
            case 'artifacts-deletion':
                $this->deletion_controller->index($this->csrf);
                break;
            case 'artifacts-deletion-update-limit':
                $this->csrf->check();
                $this->deletion_controller->update($request, $response);
                break;
            case 'emailgateway':
            default:
                $this->mailgateway_controller->index($this->csrf, $response);
        }
    }

    private function checkUserIsSiteadmin(PFUser $user, Response $response)
    {
        if (! $user->isSuperUser()) {
            $response->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('global', 'perm_denied'));
            $response->redirect('/');
        }
    }
}
