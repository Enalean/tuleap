<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

use CSRFSynchronizerToken;
use PFUser;
use TemplateRendererFactory;
use Tracker_IDisplayTrackerLayout;
use Tracker_Workflow_Action;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Tracker\Tracker;
use Tuleap\Tracker\Webhook\Webhook;
use Tuleap\Tracker\Webhook\WebhookFactory;
use Tuleap\Tracker\Webhook\WebhookLogsRetriever;

class AdminWebhooks extends Tracker_Workflow_Action
{
    public const string FUNC_ADMIN_WEBHOOKS = 'admin-webhooks';

    /**
     * @var WebhookFactory
     */
    private $webhook_factory;
    /**
     * @var WebhookLogsRetriever
     */
    private $logs_retriever;

    public function __construct(Tracker $tracker, WebhookFactory $webhook_factory, WebhookLogsRetriever $logs_retriever)
    {
        parent::__construct($tracker);

        $this->webhook_factory = $webhook_factory;
        $this->logs_retriever  = $logs_retriever;
    }

    /**
     * Process the request
     */
    #[\Override]
    public function process(Tracker_IDisplayTrackerLayout $layout, \Tuleap\HTTPRequest $request, PFUser $current_user)
    {
        $assets = new IncludeAssets(__DIR__ . '/../../../../scripts/tracker-admin/frontend-assets', '/assets/trackers/tracker-admin');
        $GLOBALS['Response']->addCssAsset(new CssAssetWithoutVariantDeclinaisons($assets, 'webhooks-style'));
        $GLOBALS['Response']->addJavascriptAsset(new JavascriptAsset($assets, 'webhooks.js'));

        $this->displayHeaderBurningParrot($layout, dgettext('tuleap-tracker', 'Webhooks'));

        $presenter = new AdminPresenter(
            $this->getWebhookPresenters(),
            $this->getCSRFSynchronizerToken(),
            $this->tracker
        );

        $renderer = TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../../../templates/webhook');
        $renderer->renderToPage('administration', $presenter);

        $this->displayFooterBurningParrot($layout);
    }

    /**
     * @return CSRFSynchronizerToken
     */
    private function getCSRFSynchronizerToken()
    {
        $url = '/plugins/tracker/?' . http_build_query([
            'func'    => 'admin-webhooks',
            'tracker' => $this->tracker->getId(),
        ]);

        return new CSRFSynchronizerToken($url);
    }

    /**
     * @return array
     */
    private function getWebhookPresenters()
    {
        $webhook_presenters = [];
        foreach ($this->webhook_factory->getWebhooksForTracker($this->tracker) as $webhook) {
            $webhook_presenters[] = new WebhookPresenter($webhook, $this->getLogsForWebhook($webhook));
        }

        return $webhook_presenters;
    }

    /**
     * @return WebhookLogPresenter[]
     */
    private function getLogsForWebhook(Webhook $webhook)
    {
        $logs = [];
        foreach ($this->logs_retriever->getLogsForWebhook($webhook) as $row) {
            $logs[] = new WebhookLogPresenter($row['created_on'], $row['status']);
        }

        return $logs;
    }
}
