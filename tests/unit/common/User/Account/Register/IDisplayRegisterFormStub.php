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

use Tuleap\Layout\BaseLayout;

final class IDisplayRegisterFormStub implements IDisplayRegisterForm
{
    private bool $has_been_displayed                     = false;
    private bool $has_been_displayed_with_possible_issue = false;
    private ?RegisterFormContext $context                = null;

    private function __construct()
    {
    }

    public static function buildSelf(): self
    {
        return new self();
    }

    #[\Override]
    public function display(\HTTPRequest $request, BaseLayout $layout, RegisterFormContext $context): void
    {
        $this->has_been_displayed = true;
        $this->context            = $context;
    }

    public function isAdmin(): bool
    {
        return $this->context && $this->context->is_admin;
    }

    public function isPasswordNeeded(): bool
    {
        return $this->context && $this->context->is_password_needed;
    }

    public function hasBeenDisplayed(): bool
    {
        return $this->has_been_displayed;
    }

    public function hasBeenDisplayedWithPossibleIssue(): bool
    {
        return $this->has_been_displayed_with_possible_issue;
    }

    #[\Override]
    public function displayWithPossibleIssue(
        \HTTPRequest $request,
        BaseLayout $layout,
        RegisterFormContext $context,
        ?RegisterFormValidationIssue $issue,
    ): void {
        $this->has_been_displayed_with_possible_issue = true;
        $this->context                                = $context;
    }
}
