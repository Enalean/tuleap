<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\MailGateway;

use Config_LocalIncFinder;
use EventManager;
use CSRFSynchronizerToken;
use Response;
use Codendi_Request;
use Feedback;
use Event;
use Tuleap\Admin\AdminPageRenderer;

class MailGatewayConfigController
{
    private static $TEMPLATE = 'siteadmin-config/emailgateway';

    /** @var MailGatewayConfig */
    private $config;

    /** @var Config_LocalIncFinder */
    private $localincfinder;

    /** @var EventManager */
    private $event_manager;

    /** @var AdminPageRenderer */
    private $admin_page_rendered;

    public function __construct(
        MailGatewayConfig $config,
        Config_LocalIncFinder $localincfinder,
        EventManager $event_manager,
        AdminPageRenderer $admin_page_rendered,
    ) {
        $this->config              = $config;
        $this->localincfinder      = $localincfinder;
        $this->event_manager       = $event_manager;
        $this->admin_page_rendered = $admin_page_rendered;
    }

    public function index(CSRFSynchronizerToken $csrf, Response $response)
    {
        $title = dgettext('tuleap-tracker', 'Trackers');

        $this->admin_page_rendered->renderANoFramedPresenter(
            $title,
            TRACKER_TEMPLATE_DIR,
            self::$TEMPLATE,
            new MailGatewayConfigPresenter(
                $csrf,
                $title,
                $this->localincfinder->getLocalIncPath(),
                $this->config,
            )
        );
    }

    public function update(Codendi_Request $request, Response $response)
    {
        $this->updateEmailGatewayMode($request, $response);

        $response->redirect($_SERVER['REQUEST_URI']);
    }

    private function updateEmailGatewayMode(Codendi_Request $request, Response $response)
    {
        $emailgateway_mode = $request->get('emailgateway_mode');
        if ($emailgateway_mode) {
            $this->config->setEmailgatewayMode($emailgateway_mode);
            $response->addFeedback(Feedback::INFO, dgettext('tuleap-tracker', 'Successfully updated, the modification will be active in a few minutes.'));
        }
        $this->event_manager->processEvent(Event::UPDATE_ALIASES, null);
    }
}
