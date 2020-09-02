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

    public function __construct(
        PFUser $user,
        $notifications_placeholder,
        HelpDropdownPresenter $help_dropdown_presenter,
        $body_class
    ) {
        $this->user_locale               = $user->getLocale();
        $this->user_id                   = $user->getId();
        $this->notifications_placeholder = $notifications_placeholder;
        $this->body_class                = $body_class;
        $this->help_dropdown             = $help_dropdown_presenter;
    }
}
