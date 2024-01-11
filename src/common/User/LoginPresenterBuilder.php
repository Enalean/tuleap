<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

use Tuleap\User\Account\RegistrationGuardEvent;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class User_LoginPresenterBuilder
{
    public function __construct(
        private readonly EventManager $event_manager,
    ) {
    }

    public function build(string $return_to, int $printer_version, string $form_loginname, CSRFSynchronizerToken $login_csrf, string $prompt_param): User_LoginPresenter
    {
        $additional_connectors = $this->event_manager->dispatch(new Tuleap\User\AdditionalConnectorsCollector($return_to));
        assert($additional_connectors instanceof Tuleap\User\AdditionalConnectorsCollector);

        $registration_guard = $this->event_manager->dispatch(new RegistrationGuardEvent());

        $presenter = new User_LoginPresenter(
            $return_to,
            $printer_version,
            $form_loginname,
            $login_csrf,
            $prompt_param,
            $additional_connectors,
            $registration_guard->isRegistrationPossible(),
        );

        $authoritative = false;

        $this->event_manager->processEvent(
            'login_presenter',
            [
                'presenter'     => &$presenter,
                'authoritative' => &$authoritative,
            ]
        );

        return $presenter;
    }

    public function buildForHomepage(CSRFSynchronizerToken $login_csrf): User_LoginPresenter
    {
        $return_to       = '';
        $printer_version = 0;
        $form_loginname  = '';

        return $this->build($return_to, $printer_version, $form_loginname, $login_csrf, '');
    }
}
