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
use PFUser;
use Tuleap\Layout\BaseLayout;
use Tuleap\User\IGenerateMailConfirmationCode;

final class RegisterFormProcessor implements IProcessRegisterForm
{
    public function __construct(
        private IValidateFormAndCreateUser $form_handler,
        private IGenerateMailConfirmationCode $mail_confirmation_code_generator,
        private AfterSuccessfulUserRegistrationHandler $after_successful_user_registration,
        private IDisplayRegisterForm $form_displayer,
    ) {
    }

    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, RegisterFormContext $context): void
    {
        $mail_confirm_code = $this->mail_confirmation_code_generator->getConfirmationCode();

        $this->form_handler
            ->process($request, $context, $mail_confirm_code)
            ->match(
                function (PFUser $new_user) use ($request, $mail_confirm_code, $layout, $context) {
                    $this->after_successful_user_registration->afterSuccessfullUserRegistration(
                        $new_user,
                        $request,
                        $layout,
                        $mail_confirm_code,
                        $context,
                    );
                },
                function (?RegisterFormValidationIssue $issue) use ($request, $layout, $context) {
                    $this->form_displayer->displayWithPossibleIssue($request, $layout, $context, $issue);
                }
            );
    }
}
