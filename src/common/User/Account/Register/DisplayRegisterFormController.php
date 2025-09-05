<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\User\Account\Register;

use HTTPRequest;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\Request\ForbiddenException;
use Tuleap\User\Account\RegistrationGuardEvent;

final class DisplayRegisterFormController implements DispatchableWithRequestNoAuthz, DispatchableWithBurningParrot
{
    public function __construct(
        private IDisplayRegisterForm $form_displayer,
        private EventDispatcherInterface $event_dispatcher,
        private IExtractInvitationToEmail $invitation_to_email_request_extractor,
    ) {
    }

    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        if (! $request->getCurrentUser()->isAnonymous()) {
            $this->alreadyLoggedIn($layout);

            return;
        }

        $registration_guard = $this->event_dispatcher->dispatch(new RegistrationGuardEvent());
        if (! $registration_guard->isRegistrationPossible()) {
            throw new ForbiddenException();
        }

        $invitation_to_email = $this->invitation_to_email_request_extractor->getInvitationToEmail($request);
        if ($invitation_to_email && $invitation_to_email->created_user_id) {
            $this->previouslyRegistered($layout);

            return;
        }

        $is_password_needed = $this->event_dispatcher
            ->dispatch(new BeforeUserRegistrationEvent($request))
            ->isPasswordNeeded();

        $this->form_displayer->display(
            $request,
            $layout,
            RegisterFormContext::forAnonymous(
                $is_password_needed,
                $invitation_to_email,
            ),
        );
    }

    private function alreadyLoggedIn(BaseLayout $layout): void
    {
        $layout->addFeedback(\Feedback::INFO, _('No need to register, you are already logged in.'));
        $layout->redirect('/my/');
    }

    private function previouslyRegistered(BaseLayout $layout): void
    {
        $layout->addFeedback(
            \Feedback::INFO,
            _('You have already used your invitation to register, you can now log in.')
        );
        $layout->redirect('/account/login.php');
    }
}
