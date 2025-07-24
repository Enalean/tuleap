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

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Layout\BaseLayout;

final class IDisplayConfirmationPageStub implements IDisplayConfirmationPage
{
    private bool $has_confirmation_for_admin_been_displayed  = false;
    private bool $has_confirmation_link_error_been_displayed = false;
    private bool $has_confirmation_link_sent_been_displayed  = false;
    private bool $has_wait_for_approvale_been_displayed      = false;

    private function __construct()
    {
    }

    public static function buildSelf(): self
    {
        return new self();
    }

    #[\Override]
    public function displayConfirmationForAdmin(BaseLayout $layout, \PFUser $new_user, ConcealedString $password): void
    {
        $this->has_confirmation_for_admin_been_displayed = true;
    }

    #[\Override]
    public function displayConfirmationLinkError(BaseLayout $layout): void
    {
        $this->has_confirmation_link_error_been_displayed = true;
    }

    #[\Override]
    public function displayConfirmationLinkSent(BaseLayout $layout, \PFUser $new_user): void
    {
        $this->has_confirmation_link_sent_been_displayed = true;
    }

    #[\Override]
    public function displayWaitForApproval(BaseLayout $layout, \PFUser $new_user): void
    {
        $this->has_wait_for_approvale_been_displayed = true;
    }

    public function hasConfirmationForAdminBeenDisplayed(): bool
    {
        return $this->has_confirmation_for_admin_been_displayed;
    }

    public function hasConfirmationLinkErrorBeenDisplayed(): bool
    {
        return $this->has_confirmation_link_error_been_displayed;
    }

    public function hasConfirmationLinkSentBeenDisplayed(): bool
    {
        return $this->has_confirmation_link_sent_been_displayed;
    }

    public function hasWaitForApprovaleBeenDisplayed(): bool
    {
        return $this->has_wait_for_approvale_been_displayed;
    }
}
