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
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\User\Account\RegistrationGuardEvent;

final class ProcessRegisterFormController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    public function __construct(
        private IProcessRegisterForm $form_processor,
        private EventDispatcherInterface $event_dispatcher,
        private \EventManager $event_manager, // needed for legacy event 'before_register'
    ) {
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $registration_guard = $this->event_dispatcher->dispatch(new RegistrationGuardEvent());
        if (! $registration_guard->isRegistrationPossible()) {
            throw new ForbiddenException();
        }

        $is_password_needed = true;
        $this->event_manager->processEvent(
            'before_register',
            [
                'request'                      => $request,
                'is_registration_confirmation' => false,
                'is_password_needed'           => &$is_password_needed,
            ]
        );

        $is_admin = false;
        $this->form_processor->process($request, $layout, $is_admin, $is_password_needed);
    }
}
