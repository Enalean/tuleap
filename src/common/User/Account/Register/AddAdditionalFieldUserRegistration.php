<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

final class AddAdditionalFieldUserRegistration implements \Tuleap\Event\Dispatchable
{
    public const NAME                         = 'addAdditionalFieldUserRegistration';
    private string $additional_fields_in_html = '';

    public function __construct(
        private BaseLayout $layout,
        private \Codendi_Request $request,
        public readonly ?RegisterFormValidationIssue $validation_issue,
    ) {
    }

    public function getLayout(): BaseLayout
    {
        return $this->layout;
    }

    public function getRequest(): \Codendi_Request
    {
        return $this->request;
    }

    public function getAdditionalFieldsInHtml(): string
    {
        return $this->additional_fields_in_html;
    }

    public function appendAdditionalFieldsInHtml(string $additional_field_in_html): void
    {
        $this->additional_fields_in_html .= $additional_field_in_html;
    }
}
