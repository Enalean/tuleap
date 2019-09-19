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

namespace Tuleap\News\Admin;

use CSRFSynchronizerToken;
use Feedback;
use HTTPRequest;

class AdminNewsRouter
{
    /**
     * @var \Tuleap\News\Admin\AdminNewsController
     */
    private $admin_news_controller;

    public function __construct(
        AdminNewsController $admin_news_controller
    ) {
        $this->admin_news_controller = $admin_news_controller;
    }

    public function route(HTTPRequest $request)
    {
        if ($request->get('action')) {
            $token  = $this->getCSRF();
            $token->check();
            $this->update($request);
        } elseif ($request->get('publish')) {
            $this->displayNewsDetails($request);
        } else {
            $this->displayNewsList($request);
        }
    }

    private function displayNewsList(HTTPRequest $request)
    {
        if (! $request->get('pane') || $request->get('pane') === 'waiting_publication') {
            $this->admin_news_controller->displayWaitingPublicationNewsPresenter();
        } elseif ($request->get('pane') === 'rejected_news') {
            $this->admin_news_controller->displayRejectedNewsPresenter();
        } elseif ($request->get('pane') === 'published_news') {
            $this->admin_news_controller->displayPublishedNewsPresenter();
        }
    }

    private function displayNewsDetails(HTTPRequest $request)
    {
        try {
            $id          = $request->get('id');
            $current_tab = $request->get('current_tab');
            $this->admin_news_controller->displayDetailsNewsPresenter($id, $current_tab);
        } catch (AdminNewsFindException $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('news_admin_index', 'not_found_err')
            );
        }
    }

    private function update(HTTPRequest $request)
    {
        $action      = $request->get('action');
        $current_tab = $request->get('current_tab');

        if ($action === 'publish') {
            $request->set('status', NewsRetriever::NEWS_STATUS_PUBLISHED);
        } elseif ($action === 'reject') {
            $request->set('status', NewsRetriever::NEWS_STATUS_REJECTED);
        }

        try {
            $this->admin_news_controller->update($request);

            if ($action === 'publish') {
                $GLOBALS['Response']->addFeedback(
                    Feedback::INFO,
                    $GLOBALS['Language']->getText('news_admin_index', 'news_updated')
                );
            } elseif ($action === 'reject') {
                $GLOBALS['Response']->addFeedback(
                    Feedback::INFO,
                    $GLOBALS['Language']->getText('news_admin_index', 'news_rejected')
                );
            }
        } catch (AdminNewsMissingTitleException $exception) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('news_admin_index', 'missing_title'));
        } catch (AdminNewsUpdateException $exception) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('news_admin_index', 'update_err'));
        }

        $GLOBALS['Response']->redirect('/admin/news?pane=' . $current_tab);
    }

    private function getCSRF()
    {
        return new CSRFSynchronizerToken('/admin/news');
    }
}
