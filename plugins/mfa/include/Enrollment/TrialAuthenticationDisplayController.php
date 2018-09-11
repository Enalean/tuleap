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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\MFA\Enrollment;

use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\MFA\Enrollment\TOTP\TOTPEnroller;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;

class TrialAuthenticationDisplayController implements DispatchableWithRequest
{
    /**
     * @var \TemplateRenderer
     */
    private $template_renderer;
    /**
     * @var TOTPEnroller
     */
    private $enroller;

    public function __construct(\TemplateRenderer $template_renderer, TOTPEnroller $enroller)
    {
        $this->template_renderer = $template_renderer;
        $this->enroller          = $enroller;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $current_user = $request->getCurrentUser();
        if (! $current_user->isLoggedIn()) {
            throw new ForbiddenException();
        }
        if (! $this->enroller->isUserEnrolled($current_user)) {
            $layout->addFeedback(\Feedback::INFO, 'Please enroll yourself first');
            $layout->redirect('/plugins/mfa/enroll');
        }

        $csrf_token = new \CSRFSynchronizerToken($request->getFromServer('REQUEST_URI'));
        $layout->header(['title' => 'Test your two-factor authentication']);
        $this->template_renderer->renderToPage(
            'trial_enrollment',
            ['csrf_token' => $csrf_token]
        );
    }
}
