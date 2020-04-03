<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\XML;

use Psr\Log\LoggerInterface;

class TrackerXmlImportFeedbackCollector
{
    /**
     * @var string[]
     */
    private $warns = [];
    /**
     * @var string[]
     */
    private $errors = [];

    public function getWarnings(): array
    {
        return $this->warns;
    }

    public function addWarnings(string $warn): void
    {
        $this->warns[] = $warn;
    }

    public function displayWarnings(LoggerInterface $logger): void
    {
        foreach ($this->warns as $warn) {
            $GLOBALS['Response']->addFeedback(\Feedback::WARN, $warn);
            $logger->warning($warn);
        }
    }

    public function addErrors(string $error): void
    {
        $this->errors[] = $error;
    }

    public function displayErrors(LoggerInterface $logger): void
    {
        foreach ($this->errors as $error) {
            $GLOBALS['Response']->addFeedback(\Feedback::ERROR, $error);
            $logger->warning($error);
        }
    }
}
