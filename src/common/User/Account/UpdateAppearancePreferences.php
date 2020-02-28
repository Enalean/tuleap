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

use BaseLanguage;
use CSRFSynchronizerToken;
use Feedback;
use HTTPRequest;
use PFUser;
use ThemeVariant;
use ThemeVariantColor;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use UserHelper;
use UserManager;

class UpdateAppearancePreferences implements DispatchableWithRequest
{
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var BaseLanguage
     */
    private $language;
    /**
     * @var ThemeVariant
     */
    private $variant;

    public function __construct(
        CSRFSynchronizerToken $csrf_token,
        UserManager $user_manager,
        BaseLanguage $language,
        ThemeVariant $variant
    ) {
        $this->csrf_token   = $csrf_token;
        $this->user_manager = $user_manager;
        $this->language     = $language;
        $this->variant      = $variant;
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

        $this->csrf_token->check(DisplayAppearanceController::URL);

        $something_has_been_updated = $this->setNewColor($request, $layout, $user);
        $something_has_been_updated = $this->setNewDisplayDensity($request, $layout, $user) || $something_has_been_updated;
        $something_has_been_updated = $this->setNewAccessibilityMode($request, $layout, $user) || $something_has_been_updated;
        $something_has_been_updated = $this->setNewUsernameDisplay($request, $layout, $user) || $something_has_been_updated;

        $needs_update_db = $this->prepareNewLanguage($request, $layout, $user);
        if (! $needs_update_db && ! $something_has_been_updated) {
            $layout->addFeedback(Feedback::INFO, _('Nothing changed'));
        } else {
            if (! $needs_update_db && $something_has_been_updated) {
                $layout->addFeedback(Feedback::INFO, _('User preferences successfully updated'));
            } else {
                if ($this->user_manager->updateDb($user)) {
                    $layout->addFeedback(Feedback::INFO, _('User preferences successfully updated'));
                } else {
                    $layout->addFeedback(Feedback::ERROR, _('Unable to update user preferences'));
                }
            }
        }

        $layout->redirect(DisplayAppearanceController::URL);
    }

    private function setNewAccessibilityMode(HTTPRequest $request, BaseLayout $layout, PFUser $user): bool
    {
        $has_accessibility   = (bool) $user->getPreference(PFUser::ACCESSIBILITY_MODE);
        $wants_accessibility = (bool) $request->get('accessibility_mode');

        if ($has_accessibility === $wants_accessibility) {
            return false;
        }

        if (! $user->setPreference(PFUser::ACCESSIBILITY_MODE, $wants_accessibility ? '1' : '0')) {
            $layout->addFeedback(Feedback::ERROR, _('Unable to change the accessibility mode.'));

            return false;
        }

        return true;
    }

    private function setNewUsernameDisplay(HTTPRequest $request, BaseLayout $layout, PFUser $user): bool
    {
        $current_username_display = (int) $user->getPreference(PFUser::PREFERENCE_NAME_DISPLAY_USERS);
        $new_username_display     = (int) $request->get('username_display');

        if ($current_username_display === $new_username_display) {
            return false;
        }

        $allowed = [
            UserHelper::PREFERENCES_NAME_AND_LOGIN,
            UserHelper::PREFERENCES_LOGIN_AND_NAME,
            UserHelper::PREFERENCES_LOGIN,
            UserHelper::PREFERENCES_REAL_NAME
        ];
        if (! in_array($new_username_display, $allowed, true)) {
            $layout->addFeedback(Feedback::ERROR, _('Submitted username display is not valid.'));

            return false;
        }

        if (! $user->setPreference(PFUser::PREFERENCE_NAME_DISPLAY_USERS, (string) $new_username_display)) {
            $layout->addFeedback(Feedback::ERROR, _('Unable to change the accessibility mode.'));

            return false;
        }

        return true;
    }

    private function setNewDisplayDensity(HTTPRequest $request, BaseLayout $layout, PFUser $user): bool
    {
        $preference   = $user->getPreference(PFUser::PREFERENCE_DISPLAY_DENSITY);
        $is_condensed = $preference === PFUser::DISPLAY_DENSITY_CONDENSED;

        $wants_condensed = (string) $request->get('display_density') === PFUser::DISPLAY_DENSITY_CONDENSED;

        if ($is_condensed === $wants_condensed) {
            return false;
        }

        if ($wants_condensed) {
            $success = $user->setPreference(PFUser::PREFERENCE_DISPLAY_DENSITY, PFUser::DISPLAY_DENSITY_CONDENSED);
        } else {
            $success = $user->delPreference(PFUser::PREFERENCE_DISPLAY_DENSITY);
        }

        if (! $success) {
            $layout->addFeedback(Feedback::ERROR, _('Unable to change the display density.'));
        }

        return $success;
    }

    private function setNewColor(HTTPRequest $request, BaseLayout $layout, PFUser $user): bool
    {
        $color = (string) $request->get('color');
        if (! $color) {
            return false;
        }

        $current_theme_variant = $this->variant->getVariantForUser($user);
        $current_color         = ThemeVariantColor::buildFromVariant($current_theme_variant);

        if ($current_color->getName() === $color) {
            return false;
        }

        $new_variant = ThemeVariantColor::buildFromName($color)->getVariant();
        if (! in_array($new_variant, $this->variant->getAllowedVariants(), true)) {
            $layout->addFeedback(Feedback::ERROR, _('The chosen color is not allowed.'));

            return false;
        }

        if (! $user->setPreference(ThemeVariant::PREFERENCE_NAME, $new_variant)) {
            $layout->addFeedback(Feedback::ERROR, _('Unable to change the color.'));

            return false;
        }

        return true;
    }

    private function prepareNewLanguage(HTTPRequest $request, BaseLayout $layout, PFUser $user): bool
    {
        $language_id = (string) $request->get('language_id');
        if (! $language_id) {
            return false;
        }

        if (! $this->language->isLanguageSupported($language_id)) {
            $layout->addFeedback(Feedback::ERROR, _('The submitted language is not supported.'));

            return false;
        }

        if ($language_id === $user->getLanguageID()) {
            return false;
        }

        $user->setLanguageID($language_id);

        return true;
    }
}
