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

final class ProcessRegisterFormController implements DispatchableWithRequestNoAuthz, DispatchableWithBurningParrot
{
    public function __construct(
        private IProcessRegisterForm $form_processor,
        private EventDispatcherInterface $event_dispatcher,
        private IExtractInvitationToEmail $invitation_to_email_request_extractor,
    ) {
    }

    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        if (! $request->getCurrentUser()->isAnonymous()) {
            throw new ForbiddenException();
        }

        $registration_guard = $this->event_dispatcher->dispatch(new RegistrationGuardEvent());
        if (! $registration_guard->isRegistrationPossible()) {
            throw new ForbiddenException();
        }

        $invitation_to_email = $this->invitation_to_email_request_extractor->getInvitationToEmail($request);
        if ($invitation_to_email && $invitation_to_email->created_user_id) {
            throw new ForbiddenException();
        }

        $is_password_needed = $this->event_dispatcher
            ->dispatch(new BeforeUserRegistrationEvent($request))
            ->isPasswordNeeded();

        $this->form_processor->process(
            $request,
            $layout,
            RegisterFormContext::forAnonymous(
                $is_password_needed,
                $invitation_to_email,
            ),
        );
    }
}
