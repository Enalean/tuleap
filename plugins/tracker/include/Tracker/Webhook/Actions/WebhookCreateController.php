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

use CSRFSynchronizerToken;
use HTTPRequest;
use Tracker;
use TrackerFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Tracker\Webhook\WebhookDao;

class WebhookCreateController implements DispatchableWithRequest
{

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var WebhookDao
     */
    private $webhook_dao;

    /**
     * @var WebhookURLValidator
     */
    private $validator;

    public function __construct(
        WebhookDao $webhook_dao,
        TrackerFactory $tracker_factory,
        WebhookURLValidator $validator
    ) {
        $this->tracker_factory = $tracker_factory;
        $this->webhook_dao     = $webhook_dao;
        $this->validator       = $validator;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $tracker_id  = $request->get('tracker_id');
        $webhook_url = $request->get('webhook_url');

        if (! $tracker_id || ! $webhook_url) {
            $layout->redirect('/');
        }

        $tracker = $this->tracker_factory->getTrackerById($tracker_id);
        if (! $tracker) {
            throw new NotFoundException();
        }

        $redirect_url = $this->getAdminWebhooksURL($tracker);
        $webhook_url  = $this->validator->getValidURL($request, $layout, $redirect_url);

        $user = $request->getCurrentUser();
        if (! $tracker->userIsAdmin($user)) {
            throw new ForbiddenException();
        }

        $csrf = $this->getCSRFSynchronizerToken($tracker);
        $csrf->check();

        $this->webhook_dao->save($tracker_id, $webhook_url);

        $layout->addFeedback(
            \Feedback::INFO,
            dgettext('tuleap-tracker', 'Webhook sucessfully created')
        );

        $layout->redirect($this->getAdminWebhooksURL($tracker));
    }

    /**
     * @return CSRFSynchronizerToken
     */
    private function getCSRFSynchronizerToken(Tracker $tracker)
    {
        return new CSRFSynchronizerToken($this->getAdminWebhooksURL($tracker));
    }

    private function getAdminWebhooksURL(Tracker $tracker)
    {
        return '/plugins/tracker/?' . http_build_query([
            "func"    => "admin-webhooks",
            "tracker" => $tracker->getId()
        ]);
    }
}
