<?php
/**
 * Copyright (c) Enalean, 2013 - present. All Rights Reserved.
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

use Tuleap\HelpDropdown\HelpDropdownPresenter;
use Tuleap\InviteBuddy\InviteBuddiesPresenter;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class FlamingParrot_BodyPresenter
{
    /** @var string */
    public $notifications_placeholder;

    /** @var array */
    public $body_class;

    /** @var string */
    public $user_locale;
    /**
     * @var HelpDropdownPresenter
     */
    public $help_dropdown;
    /**
     * @var int
     * @psalm-readonly
     */
    public $user_id;
    /**
     * @var InviteBuddiesPresenter
     * @psalm-readonly
     */
    public $invite_buddies_presenter;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $has_platform_banner;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $platform_banner_is_visible;
    /**
     * @var string
     * @psalm-readonly
     */
    public $purified_platform_banner;
    /**
     * @var string
     * @psalm-readonly
     */
    public $platform_banner_importance;

    public function __construct(
        PFUser $user,
        $notifications_placeholder,
        HelpDropdownPresenter $help_dropdown_presenter,
        $body_class,
        InviteBuddiesPresenter $invite_buddies_presenter,
        ?\Tuleap\Platform\Banner\BannerDisplay $platform_banner
    ) {
        $this->user_locale               = $user->getLocale();
        $this->user_id                   = $user->getId();
        $this->notifications_placeholder = $notifications_placeholder;
        $this->body_class                = $body_class;
        $this->help_dropdown             = $help_dropdown_presenter;
        $this->invite_buddies_presenter  = $invite_buddies_presenter;

        $this->has_platform_banner        = $platform_banner !== null;
        $this->platform_banner_is_visible = $platform_banner && $platform_banner->isVisible();
        $this->platform_banner_importance = $platform_banner ? $platform_banner->getImportance() : '';
        $this->purified_platform_banner   = "";
        if ($platform_banner) {
            $this->purified_platform_banner = \Codendi_HTMLPurifier::instance()->purify(
                $platform_banner->getMessage(),
                Codendi_HTMLPurifier::CONFIG_MINIMAL_FORMATTING_NO_NEWLINE,
            );
        }
    }
}
