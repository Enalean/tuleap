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

namespace Tuleap\Tracker\Artifact\Closure;

use Psr\Log\Test\TestLogger;
use Tuleap\Event\Events\PotentialReferencesReceived;

final class ArtifactClosingReferencesHandlerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private string $text_with_potential_references;
    private TestLogger $logger;

    protected function setUp(): void
    {
        $this->text_with_potential_references = <<<EOF
closes art #123
implements art #234
EOF;

        $this->logger = new TestLogger();
    }

    public function handlePotentialReferencesReceived(): void
    {
        $handler = new ArtifactClosingReferencesHandler($this->logger);
        $handler->handlePotentialReferencesReceived(
            new PotentialReferencesReceived($this->text_with_potential_references)
        );
    }

    public function testItLogsADebugMessage(): void
    {
        $this->handlePotentialReferencesReceived();
        self::assertTrue($this->logger->hasDebugThatContains('Searching for references in text'));
    }
}
