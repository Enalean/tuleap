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

namespace Tuleap\OAuth2Server\Administration;

use Tuleap\OAuth2ServerCore\App\AppDao;

final class OAuth2AppProjectVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AppDao
     */
    private $app_dao;
    /**
     * @var OAuth2AppProjectVerifier
     */
    private $project_verifier;

    protected function setUp(): void
    {
        $this->app_dao          = $this->createMock(AppDao::class);
        $this->project_verifier = new OAuth2AppProjectVerifier($this->app_dao);
    }

    public function testAppIsPartOfTheExpectedProject(): void
    {
        $expected_project = \Project::buildForTest();
        $this->app_dao->method('searchProjectIDByClientID')->willReturn((int) $expected_project->getID());

        self::assertTrue($this->project_verifier->isAppPartOfTheExpectedProject($expected_project, 1));
        self::assertFalse($this->project_verifier->isASiteLevelApp(1));
    }

    public function testAppIsASiteLevelApp(): void
    {
        $this->app_dao->method('searchProjectIDByClientID')->willReturn(null);

        self::assertTrue($this->project_verifier->isASiteLevelApp(1));
    }

    /**
     * @dataProvider dataProviderAppNotPartOfExpectedProject
     */
    public function testAppIsNotPartOfTheExpectedProject(?int $app_project_id): void
    {
        $this->app_dao->method('searchProjectIDByClientID')->willReturn($app_project_id);

        self::assertFalse($this->project_verifier->isAppPartOfTheExpectedProject(\Project::buildForTest(), 1));
    }

    public function dataProviderAppNotPartOfExpectedProject(): array
    {
        return [
            'App is a site level app'     => [null],
            'App part of another project' => [404],
        ];
    }
}
