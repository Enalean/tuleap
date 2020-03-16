<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\User\Account;

use CSRFSynchronizerToken;
use Feedback;
use HTTPRequest;
use PFUser;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;

class UpdateEditionController implements DispatchableWithRequest
{
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;

    public function __construct(CSRFSynchronizerToken $csrf_token)
    {
        $this->csrf_token = $csrf_token;
    }

    /**
     * @inheritDoc
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $user = $request->getCurrentUser();
        if ($user->isAnonymous()) {
            throw new ForbiddenException();
        }

        $this->csrf_token->check(DisplayEditionController::URL);

        $this->updatePreferences($request, $layout, $user);

        $layout->redirect(DisplayEditionController::URL);
    }

    private function updatePreferences(HTTPRequest $request, BaseLayout $layout, PFUser $user): void
    {
        $text_default_format = $user->getPreference(PFUser::PREFERENCE_NAME_EDITION_DEFAULT_FORMAT);
        $csv_separator       = $user->getPreference(PFUser::PREFERENCE_NAME_CSV_SEPARATOR);
        $csv_dateformat      = $user->getPreference(PFUser::PREFERENCE_NAME_CSV_DATEFORMAT);

        $wants_text_default_format = (string) $request->get('user_text_default_format');
        $wants_csv_separator       = (string) $request->get('user_csv_separator');
        $wants_csv_dateformat      = (string) $request->get('user_csv_dateformat');

        if ($text_default_format === $wants_text_default_format
            && $csv_separator === $wants_csv_separator
            && $csv_dateformat === $wants_csv_dateformat
        ) {
            $layout->addFeedback(Feedback::INFO, _('Nothing changed'));

            return;
        }

        $success = false;
        if ($text_default_format !== $wants_text_default_format) {
            $success = $this->updateDefaultFormat($wants_text_default_format, $layout, $user) || $success;
        }

        if ($csv_separator !== $wants_csv_separator) {
            $success = $this->updateCSVSeparator($wants_csv_separator, $layout, $user) || $success;
        }

        if ($csv_dateformat !== $wants_csv_dateformat) {
            $success = $this->updateCSVDateFormat($wants_csv_dateformat, $layout, $user) || $success;
        }

        if ($success) {
            $layout->addFeedback(Feedback::INFO, _('User preferences successfully updated'));
        }
    }

    private function updateDefaultFormat(string $wants_text_default_format, BaseLayout $layout, PFUser $user): bool
    {
        $allowed = [PFUser::PREFERENCE_EDITION_TEXT, PFUser::PREFERENCE_EDITION_HTML];
        if (! in_array($wants_text_default_format, $allowed, true)) {
            $layout->addFeedback(Feedback::ERROR, _('Submitted text format is not valid'));

            return false;
        }

        if (! $user->setPreference(PFUser::PREFERENCE_NAME_EDITION_DEFAULT_FORMAT, $wants_text_default_format)) {
            $layout->addFeedback(Feedback::ERROR, _('Unable to change default text format'));

            return false;
        }

        return true;
    }

    private function updateCSVSeparator(string $wants_csv_separator, BaseLayout $layout, PFUser $user): bool
    {
        $allowed = [PFUser::PREFERENCE_CSV_COMMA, PFUser::PREFERENCE_CSV_SEMICOLON, PFUser::PREFERENCE_CSV_TAB];
        if (! in_array($wants_csv_separator, $allowed, true)) {
            $layout->addFeedback(Feedback::ERROR, _('Submitted CSV separator is not valid'));

            return false;
        }

        if (! $user->setPreference(PFUser::PREFERENCE_NAME_CSV_SEPARATOR, $wants_csv_separator)) {
            $layout->addFeedback(Feedback::ERROR, _('Unable to change CSV separator'));

            return false;
        }

        return true;
    }

    private function updateCSVDateFormat(string $wants_csv_dateformat, BaseLayout $layout, PFUser $user): bool
    {
        $allowed = [PFUser::PREFERENCE_CSV_MONTH_DAY_YEAR, PFUser::PREFERENCE_CSV_DAY_MONTH_YEAR];
        if (! in_array($wants_csv_dateformat, $allowed, true)) {
            $layout->addFeedback(Feedback::ERROR, _('Submitted CSV date format is not valid'));

            return false;
        }

        if (! $user->setPreference(PFUser::PREFERENCE_NAME_CSV_DATEFORMAT, $wants_csv_dateformat)) {
            $layout->addFeedback(Feedback::ERROR, _('Unable to change CSV date format'));

            return false;
        }

        return true;
    }
}
