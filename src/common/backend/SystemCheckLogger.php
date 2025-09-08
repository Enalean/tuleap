<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

class SystemCheckLogger extends WrapperLogger
{
    private $warning_messages = [];

    #[\Override]
    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->warning_messages[] = $message;

        parent::warning($message, $context);
    }

    public function getAllWarnings()
    {
        return implode(', ', $this->warning_messages);
    }

    public function hasWarnings()
    {
        return count($this->warning_messages) > 0;
    }
}
