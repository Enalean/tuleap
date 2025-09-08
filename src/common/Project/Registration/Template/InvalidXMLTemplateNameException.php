<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\Project\Registration\Template;

final class InvalidXMLTemplateNameException extends \RuntimeException implements InvalidTemplateException
{
    /**
     * @psalm-readonly
     */
    private string $name;

    public function __construct(string $name)
    {
        parent::__construct(sprintf('The XML template "%s" provided is not valid', $name));
        $this->name = $name;
    }

    #[\Override]
    public function getI18NMessage(): string
    {
        return sprintf(_('The XML template "%s" provided is not valid'), $this->name);
    }
}
