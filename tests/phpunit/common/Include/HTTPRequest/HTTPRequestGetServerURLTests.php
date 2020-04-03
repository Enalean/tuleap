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
 *
 */

declare(strict_types=1);

abstract class HTTPRequestGetServerURLTests extends \PHPUnit\Framework\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\ForgeConfigSandbox;

    /**
     * @var HTTPRequest
     */
    protected $request;

    protected function setUp(): void
    {
        $_SERVER['REMOTE_ADDR'] = '17.18.19.20';

        $this->request = new HTTPRequest();
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTPS']);
        unset($_SERVER['HTTP_X_FORWARDED_PROTO']);
        unset($_SERVER['HTTP_HOST']);
        unset($_SERVER['REMOTE_ADDR']);
        unset($_SERVER['REMOTE_ADDR']);
    }
}
