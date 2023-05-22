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
 */

declare(strict_types=1);

namespace Tuleap\BrowserDetection;

use Tuleap\Config\ConfigKey;

/**
 * @psalm-immutable
 */
final class BrowserDeprecationMessage
{
    #[ConfigKey("Allow to disable old browser warning message")]
    public const DISABLE_OLD_BROWSER_WARNING                       = 'disable_old_browsers_warning';
    private const DISABLE_OLD_BROWSER_WARNING_CONFIRMATION_MESSAGE = 'W21_I_understand_this_only_hides_the_message_for_non_siteadmin_users_and_that_issues_related_to_old_browsers_will_still_be_present';


    /**
     * @var string
     */
    public $title;
    /**
     * @var string
     */
    public $message;
    /**
     * @var bool
     */
    public $can_be_dismiss;

    private function __construct(string $title, string $message, bool $can_be_dismiss)
    {
        $this->title          = $title;
        $this->message        = $message;
        $this->can_be_dismiss = $can_be_dismiss;
    }

    public static function fromDetectedBrowser(\PFUser $current_user, DetectedBrowser $detected_browser): ?self
    {
        if ($detected_browser->isEdgeLegacy()) {
            return new self(
                _('Your web browser is not supported'),
                _('Edge Legacy is not supported. Please upgrade to the latest version of Edge or use another modern alternative such as Firefox or Chrome.'),
                true,
            );
        }

        if (
            $detected_browser->isAnOutdatedBrowser() &&
            (
                \ForgeConfig::get(self::DISABLE_OLD_BROWSER_WARNING) !== self::DISABLE_OLD_BROWSER_WARNING_CONFIRMATION_MESSAGE ||
                $current_user->isSuperUser()
            )
        ) {
            $browser_name = $detected_browser->getName() ?? '';
            return new self(
                _('Your web browser is not supported'),
                sprintf(
                    _('You are using an outdated version of %s. You might encounter issues if you continue.'),
                    $browser_name,
                ),
                true,
            );
        }

        return null;
    }
}
