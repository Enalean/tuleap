<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Tracker\Webhook\Actions;

use Codendi_Request;
use PFUser;
use TemplateRendererFactory;
use Tracker;
use Tracker_IDisplayTrackerLayout;
use Tracker_Workflow_Action;
use Tuleap\Tracker\Webhook\Webhook;
use Tuleap\Tracker\Webhook\WebhookRetriever;

class AdminWebhooks extends Tracker_Workflow_Action
{
    const FUNC_ADMIN_WEBHOOKS = 'admin-webhooks';

    /**
     * @var WebhookRetriever
     */
    private $retriever;

    public function __construct(Tracker $tracker, WebhookRetriever $retriever)
    {
        parent::__construct($tracker);

        $this->retriever = $retriever;
    }

    /**
     * @return string eg: rules, transitions
     */
    protected function getPaneIdentifier()
    {
        return self::PANE_WEBHOOKS;
    }

    /**
     * Process the request
     */
    public function process(Tracker_IDisplayTrackerLayout $layout, Codendi_Request $request, PFUser $current_user)
    {
        $this->displayHeader($layout);

        $presenter = new AdminPresenter($this->getWebhookPresenters());

        $renderer  = TemplateRendererFactory::build()->getRenderer(TRACKER_TEMPLATE_DIR . '/webhook');
        $renderer->renderToPage('administration', $presenter);

        $this->displayFooter($layout);
    }

    /**
     * @return array
     */
    private function getWebhookPresenters()
    {
        $webhook_presenters = [];
        foreach ($this->retriever->getWebhooksForTracker($this->tracker) as $webhook) {
            $logs     = $this->getLogsForWebhook($webhook);
            $last_log = null;
            if (count($logs) > 0) {
                $last_log = $logs[0];
            }

            $webhook_presenters[] = [
                'webhook_id'  => $webhook->getId(),
                'webhook_url' => $webhook->getUrl(),
                'last_log'    => $last_log,
                'all_log'     => $logs,
            ];
        }

        return $webhook_presenters;
    }

    private function getLogsForWebhook(Webhook $webhook)
    {
        $logs = array();
        foreach ($this->retriever->getLogsForWebhook($webhook) as $row) {
            $logs[] = [
                'time'           => format_date($GLOBALS['Language']->getText('system', 'datefmt'), $row['created_on']),
                'status_message' => $row['status'],
                'status_ok'      => $row['status']{0} === '2',
            ];
        }

        return $logs;
    }
}
