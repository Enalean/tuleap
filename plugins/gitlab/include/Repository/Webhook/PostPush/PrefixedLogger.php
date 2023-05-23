<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Repository\Webhook\PostPush;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

final class PrefixedLogger extends AbstractLogger
{
    public function __construct(private LoggerInterface $logger, private string $prefix)
    {
    }

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $prefixed_message = $this->prefix . $message;
        $this->logger->log($level, $prefixed_message, $context);
    }
}
