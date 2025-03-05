<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\BrowserDetection;

use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class BrowserUsageSessionsStatisticsRetrieverTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\SessionDao
     */
    private $session_dao;
    private BrowserUsageSessionsStatisticsRetriever $retriever;

    protected function setUp(): void
    {
        $this->session_dao = $this->createStub(\SessionDao::class);
        $this->retriever   = new BrowserUsageSessionsStatisticsRetriever($this->session_dao);
    }

    public function testComputesStatistics(): void
    {
        $this->session_dao->method('countUserAgentsOfActiveSessions')->willReturn(
            [
                ['user_agent' => DetectedBrowserTest::FIREFOX_USER_AGENT_STRING, 'nb' => 2],
                ['user_agent' => DetectedBrowserTest::CHROME_USER_AGENT_STRING, 'nb' => 1],
                ['user_agent' => DetectedBrowserTest::VERY_OLD_FIREFOX_USER_AGENT_STRING, 'nb' => 5],
            ]
        );

        $stats = $this->retriever->getStatistics(new \DateTimeImmutable('@10'));

        self::assertEquals(new BrowserUsageSessions(5, 3), $stats);
    }

    public function testDefaultsTo0WhenNoActiveSessions(): void
    {
        $this->session_dao->method('countUserAgentsOfActiveSessions')->willReturn([]);

        $stats = $this->retriever->getStatistics(new \DateTimeImmutable('@10'));

        self::assertEquals(new BrowserUsageSessions(0, 0), $stats);
    }
}
