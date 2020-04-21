<?php
/**
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class URLVerificationFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGetUrlVerifictionNoPluginResponse(): void
    {
        $event_manager = \Mockery::mock(EventManager::class);
        $event_manager->shouldReceive('processEvent')->with('url_verification_instance', \Mockery::any())->once();

        $urlVerif = new URLVerificationFactory($event_manager);

        $this->assertInstanceOf(URLVerification::class, $urlVerif->getURLVerification([]));
    }

    public function testGetUrlVerificationWithPluginResponse(): void
    {
        $url_verification_instance =  new class extends URLVerification {
        };

        $event_manager = \Mockery::mock(EventManager::class);
        $event_manager->shouldReceive('processEvent')->with('url_verification_instance', \Mockery::on(function (array &$args) use ($url_verification_instance) {
            $args['url_verification'] = $url_verification_instance;
            return true;
        }))->once();

        $urlVerif = new URLVerificationFactory($event_manager);

        $this->assertSame($url_verification_instance, $urlVerif->getURLVerification([]));
    }
}
