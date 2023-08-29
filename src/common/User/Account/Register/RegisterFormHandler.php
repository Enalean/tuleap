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

use ForgeConfig;
use PFUser;
use Psr\EventDispatcher\EventDispatcherInterface;
use Rule_UserName;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Password\PasswordSanityChecker;
use User_UserStatusManager;
use Valid_RealNameFormat;
use Valid_String;

final class RegisterFormHandler implements IValidateFormAndCreateUser
{
    public function __construct(
        private AccountRegister $account_register,
        private \Account_TimezonesCollection $timezones_collection,
        private readonly EventDispatcherInterface $event_dispatcher,
    ) {
    }

    /**
     * @return Ok<PFUser>|Err<RegisterFormValidationIssue>|Err<null>
     */
    public function process(\HTTPRequest $request, RegisterFormContext $context, string $mail_confirm_code): Ok|Err
    {
        $is_admin           = $context->is_admin;
        $is_password_needed = $context->is_password_needed;

        $form_loginname = (string) $request->get('form_loginname');
        $rule_username  = new Rule_UserName();
        if (! $rule_username->isValid($form_loginname)) {
            return Result::err(
                RegisterFormValidationIssue::fromFieldName('form_loginname', $rule_username->getErrorMessage())
            );
        }

        $vRealName = new Valid_RealNameFormat('form_realname');
        $vRealName->required();
        if (! $request->valid($vRealName)) {
            $GLOBALS['Response']->addFeedback('error', _('Real name contains illegal characters.'));

            return Result::err(
                RegisterFormValidationIssue::fromFieldName('form_realname', _('Real name contains illegal characters.'))
            );
        }

        if ($is_password_needed && ! $request->existAndNonEmpty('form_pw')) {
            $GLOBALS['Response']->addFeedback('error', _('You must supply a password.'));

            return Result::err(RegisterFormValidationIssue::fromFieldName('form_pw', _('You must supply a password.')));
        }

        $timezone = (string) $request->get('timezone');
        if (! $this->timezones_collection->isValidTimezone($timezone)) {
            $GLOBALS['Response']->addFeedback('error', _('You must supply a timezone.'));

            return Result::err(
                RegisterFormValidationIssue::fromFieldName('timezone', _('You must supply a timezone.'))
            );
        }
        $is_siteadmin_approval_needed = ForgeConfig::getInt(User_UserStatusManager::CONFIG_USER_REGISTRATION_APPROVAL) === 1;
        if (
            ! $request->existAndNonEmpty('form_register_purpose')
            && (
                $is_siteadmin_approval_needed
                && ! $is_admin
            )
        ) {
            $GLOBALS['Response']->addFeedback('error', _('You must explain the purpose of your registration.'));

            return Result::err(
                RegisterFormValidationIssue::fromFieldName(
                    'form_register_purpose',
                    _('You must explain the purpose of your registration.')
                )
            );
        }

        if (! $is_admin && $context->invitation_to_email) {
            $form_email = $context->invitation_to_email->to_email;
        } else {
            $form_email = (string) $request->get('form_email');
            $rule_email = new \Rule_Email();
            if (! $rule_email->isValid($form_email)) {
                $GLOBALS['Response']->addFeedback('error', _('Invalid Email Address'));

                return Result::err(
                    RegisterFormValidationIssue::fromFieldName('form_email', _('Invalid Email Address'))
                );
            }
        }

        $password = null;
        if ($is_password_needed) {
            $password              = new ConcealedString((string) $request->get('form_pw'));
            $password_confirmation = new ConcealedString((string) $request->get('form_pw2'));
            if (! $is_admin && ! $password->isIdenticalTo($password_confirmation)) {
                $GLOBALS['Response']->addFeedback('error', _('Passwords do not match.'));

                return Result::err(RegisterFormValidationIssue::fromFieldName('form_pw', _('Passwords do not match.')));
            }

            $password_sanity_checker = PasswordSanityChecker::build();
            if (! $password_sanity_checker->check($password)) {
                foreach ($password_sanity_checker->getErrors() as $error) {
                    $GLOBALS['Response']->addFeedback('error', $error);
                }

                return Result::err(RegisterFormValidationIssue::fromFieldName('form_pw', 'Error'));
            }
        }

        $expiry_date = 0;
        if (
            $request->exist('form_expiry') && $request->get('form_expiry') != '' && ! preg_match(
                "/[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}/",
                $request->get('form_expiry')
            )
        ) {
            $GLOBALS['Response']->addFeedback(
                'error',
                _('Expiration Date entry could not be parsed. It must be in YYYY-MM-DD format.')
            );

            return Result::err(
                RegisterFormValidationIssue::fromFieldName(
                    'form_expiry',
                    _('Expiration Date entry could not be parsed. It must be in YYYY-MM-DD format.')
                )
            );
        }
        $vDate = new Valid_String();
        $vDate->required();
        if ($request->exist('form_expiry') && $vDate->validate($request->get('form_expiry'))) {
            $date_list        = preg_split("/-/D", $request->get('form_expiry'), 3);
            $unix_expiry_time = mktime(0, 0, 0, (int) $date_list[1], (int) $date_list[2], (int) $date_list[0]);
            $expiry_date      = $unix_expiry_time;
        }

        $validation_error = $this->event_dispatcher->dispatch(new BeforeRegisterFormValidationEvent($request))->getValidationError();

        if ($validation_error !== null) {
            return Result::err($validation_error);
        }

        $status = 'P';
        if ($is_admin) {
            if ($request->get('form_restricted')) {
                $status = 'R';
            } else {
                $status = 'A';
            }
        } elseif ($context->invitation_to_email && ! $is_siteadmin_approval_needed) {
            $status            = 'A';
            $mail_confirm_code = null;
        }

        $new_user = $this->account_register->register(
            $form_loginname,
            $password,
            (string) $request->get('form_realname'),
            (string) $request->get('form_register_purpose'),
            $form_email,
            $status,
            $mail_confirm_code,
            (string) $request->get('form_mail_site'),
            (string) $request->get('form_mail_va'),
            $timezone,
            $request->getCurrentUser()->getLocale(),
            $expiry_date,
            $context,
        );
        if (! $new_user) {
            return Result::err(null);
        }

        return Result::ok($new_user);
    }
}
