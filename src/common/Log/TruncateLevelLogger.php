<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class TruncateLevelLogger implements \Psr\Log\LoggerInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    private array $level_weight = [
        \Psr\Log\LogLevel::DEBUG => 0,
        \Psr\Log\LogLevel::INFO  => 10,
        \Psr\Log\LogLevel::WARNING  => 20,
        \Psr\Log\LogLevel::ERROR => 30,
    ];

    private array $should_log = [];

    /**
     * @param mixed $level
     */
    public function __construct(\Psr\Log\LoggerInterface $logger, $level)
    {
        $this->logger = $logger;
        $this->setLevel($level);
    }

    /**
     * @param mixed $min_level
     */
    private function setLevel($min_level): void
    {
        $min_weight = $this->level_weight[$min_level] ?? $this->level_weight[\Psr\Log\LogLevel::WARNING];
        foreach ($this->level_weight as $level_label => $weight) {
            $this->should_log[$level_label] = $weight >= $min_weight;
        }
    }

    public function debug(string|\Stringable $message, array $context = []): void
    {
        if ($this->should_log[\Psr\Log\LogLevel::DEBUG]) {
            $this->logger->debug($message, $context);
        }
    }

    public function info(string|\Stringable $message, array $context = []): void
    {
        if ($this->should_log[\Psr\Log\LogLevel::INFO]) {
            $this->logger->info($message, $context);
        }
    }

    public function warning(string|\Stringable $message, array $context = []): void
    {
        if ($this->should_log[\Psr\Log\LogLevel::WARNING]) {
            $this->logger->warning($message, $context);
        }
    }

    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        switch ($level) {
            case \Psr\Log\LogLevel::DEBUG:
                $this->debug($message, $context);
                break;
            case \Psr\Log\LogLevel::NOTICE:
                $this->notice($message, $context);
                break;
            case \Psr\Log\LogLevel::INFO:
                $this->info($message, $context);
                break;
            case \Psr\Log\LogLevel::WARNING:
                $this->warning($message, $context);
                break;
            case \Psr\Log\LogLevel::ERROR:
                $this->error($message, $context);
                break;
            case \Psr\Log\LogLevel::CRITICAL:
                $this->critical($message, $context);
                break;
            case \Psr\Log\LogLevel::ALERT:
                $this->alert($message, $context);
                break;
            case \Psr\Log\LogLevel::EMERGENCY:
                $this->emergency($message, $context);
                break;
        }
    }

    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->logger->emergency($message, $context);
    }

    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->logger->alert($message, $context);
    }

    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->logger->critical($message, $context);
    }

    public function notice(string|\Stringable $message, array $context = []): void
    {
        if ($this->should_log[\Psr\Log\LogLevel::DEBUG]) {
            $this->logger->notice($message, $context);
        }
    }
}
