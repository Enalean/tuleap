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

namespace Tuleap\Layout\Feedback;

/**
 * @psalm-immutable
 */
final class NewFeedback
{
    /**
     * @var string
     */
    private $level;
    /**
     * @var string
     */
    private $message;

    /**
     * @psalm-param \Feedback::* $level
     */
    public function __construct(string $level, string $message)
    {
        $this->level   = $level;
        $this->message = $message;
    }

    public static function info(string $message): self
    {
        return new self(\Feedback::INFO, $message);
    }

    public static function warn(string $message): self
    {
        return new self(\Feedback::WARN, $message);
    }

    public static function error(string $message): self
    {
        return new self(\Feedback::ERROR, $message);
    }

    public static function success(string $message): self
    {
        return new self(\Feedback::SUCCESS, $message);
    }

    public function getLevel(): string
    {
        return $this->level;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
