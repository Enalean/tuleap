<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Registration\Template\Upload;

use Psr\Log\LoggerInterface;
use Tuleap\Event\Dispatchable;

final class ArchiveWithoutDataCheckerErrorCollection implements Dispatchable
{
    /**
     * @var string[]
     */
    private array $errors = [];

    public function __construct(
        private readonly \SimpleXMLElement $xml_element,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function addError(string $error): void
    {
        $this->errors[] = $error;
    }

    /**
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getXmlElement(): \SimpleXMLElement
    {
        return $this->xml_element;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
