<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Captcha\Administration;

use CSRFSynchronizerToken;
use Feedback;
use HTTPRequest;
use PFUser;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Captcha\Configuration;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;

class DisplayController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;
    /**
     * @var AdminPageRenderer
     */
    private $renderer;
    /**
     * @var Configuration
     */
    private $configuration;

    public function __construct(Configuration $configuration, AdminPageRenderer $renderer)
    {
        $this->csrf_token    = new CSRFSynchronizerToken(CAPTCHA_BASE_URL . '/admin/');
        $this->renderer      = $renderer;
        $this->configuration = $configuration;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $current_user = $request->getCurrentUser();
        $this->checkUserIsSiteAdmin($current_user, $layout);

        $presenter = new Presenter($this->csrf_token, $this->configuration);
        $this->renderer->renderAPresenter(
            dgettext('tuleap-captcha', 'Captcha configuration'),
            CAPTCHA_TEMPLATE_DIR,
            'configuration',
            $presenter
        );
    }

    private function checkUserIsSiteAdmin(PFUser $user, BaseLayout $layout)
    {
        if (! $user->isSuperUser()) {
            $layout->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('global', 'perm_denied')
            );
            $layout->redirect('/');
        }
    }
}
