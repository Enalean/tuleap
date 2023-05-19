<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow;

use TruncateLevelLogger;

/**
 * A BackendLogger dedicated to the workflow. It prefix all message by [WF] tag.
 */
final class WorkflowBackendLogger extends TruncateLevelLogger
{
    /** @var string */
    private const WF_PREFIX = '[WF] ';

    /** @var string */
    private const INDENTATION_START = '┌ ';

    /** @var string */
    private const INDENTATION_INCREMENT = '│ ';

    /** @var string */
    private const INDENTATION_END = '└ ';

    /** @var string */
    private $indentation_prefix = '';

    /** @var string|int */
    private $fingerprint = '';

    public function debug(string|\Stringable $message, array $context = []): void
    {
        parent::debug($this->getDecoratedMessage($message), $context);
    }

    public function info(string|\Stringable $message, array $context = []): void
    {
        parent::info($this->getDecoratedMessage($message), $context);
    }

    public function warning(string|\Stringable $message, array $context = []): void
    {
        parent::warning($this->getDecoratedMessage($message), $context);
    }

    public function error(string|\Stringable $message, array $context = []): void
    {
        parent::error($this->getDecoratedMessage($message), $context);
    }

    private function getDecoratedMessage(string|\Stringable $message): string
    {
        $prefix = self::WF_PREFIX;
        if ($this->fingerprint) {
            $prefix .= "[{$this->fingerprint}] ";
        }
        $prefix .= $this->indentation_prefix;
        return $prefix . $message;
    }

    /**
     * Logs the start of a method. Used in debug mode.
     *
     * @param string $calling_method
     * @param mixed  ...              Parameters of the calling method
     */
    public function start($calling_method): void
    {
        $arguments = func_get_args();
        array_unshift($arguments, __FUNCTION__);
        array_unshift($arguments, self::INDENTATION_START);
        $this->logMethodAndItsArguments(...$arguments);
        $this->indent();
    }

    /**
     * Logs the end of a method. Used in debug mode.
     *
     * @param string $calling_method
     * @param mixed  ...              Parameters of the calling method
     */
    public function end($calling_method): void
    {
        $this->unindent();
        $arguments = func_get_args();
        array_unshift($arguments, __FUNCTION__);
        array_unshift($arguments, self::INDENTATION_END);
        $this->logMethodAndItsArguments(...$arguments);
    }

    /**
     * Define the fingerprint of the logger.
     *
     * At the name implies, once defined, a fingerprint cannot be changed.
     *
     * @param string|int $fingerprint
     */
    public function defineFingerprint($fingerprint): void
    {
        if (! $this->fingerprint) {
            $this->fingerprint = $fingerprint;
        }
    }

    private function logMethodAndItsArguments(): void
    {
        $arguments      = func_get_args();
        $prefix         = array_shift($arguments);
        $method         = ucfirst(array_shift($arguments));
        $calling_method = array_shift($arguments);
        $arguments      = implode(', ', $arguments);
        $this->debug("$prefix$method $calling_method($arguments)");
    }

    private function indent(): void
    {
        $this->indentation_prefix .= self::INDENTATION_INCREMENT;
    }

    private function unindent(): void
    {
        $this->indentation_prefix = mb_substr(
            $this->indentation_prefix,
            0,
            - mb_strlen(self::INDENTATION_INCREMENT, 'UTF-8'),
            'UTF-8'
        );
    }
}
