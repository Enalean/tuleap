<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class KASS_BodyPresenter {

    /** @var string */
    private $nav;

    /** @var string */
    private $request;

    /** @var string */
    private $title;

    /** @var string */
    private $img_root;

    /** @var string or boolean */
    private $selected_top_tab;

    /** @var Feedback */
    private $feedback;

    /** @var string */
    private $feedback_content;

    /** @var string */
    private $notifications_placeholder;

    function __construct(
        $request,
        $title,
        $img_root,
        $selected_top_tab,
        $feedback,
        $feedback_content,
        $notifications_placeholder
    ) {
        $this->request                   = $request;
        $this->title                     = $title;
        $this->img_root                  = $img_root;
        $this->selected_top_tab          = $selected_top_tab;
        $this->feedback                  = $feedback;
        $this->feedback_content          = $feedback_content;
        $this->notifications_placeholder = $notifications_placeholder;

        $this->initNavigationBar();
    }

    private function initNavigationBar() {
        $this->nav = new KASS_NavBarBuilder(
            ProjectManager::instance(),
            EventManager::instance(),
            $GLOBALS['Language'],
            HTTPRequest::instance(),
            UserManager::instance()->getCurrentUser(),
            $this->title,
            $this->img_root,
            $this->request,
            $this->selected_top_tab
        );
    }

    public function navigationBar() {
        return $this->nav->render();
    }

    public function feedback() {
        $html  = $this->feedback->display();
        $html .= $this->feedback_content;

        return $html;
    }

    public function notificationsPlaceholder() {
        return $this->notifications_placeholder;
    }

}

?>