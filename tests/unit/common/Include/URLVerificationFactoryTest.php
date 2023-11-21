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


//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class URLVerificationFactoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testGetUrlVerificationNoPluginResponse(): void
    {
        $event_manager = $this->createMock(EventManager::class);
        $event_manager->expects(self::once())->method('processEvent')->with('url_verification_instance', self::anything());

        $urlVerif = new URLVerificationFactory($event_manager);

        self::assertInstanceOf(URLVerification::class, $urlVerif->getURLVerification([]));
    }

    public function testGetUrlVerificationWithPluginResponse(): void
    {
        $url_verification_instance =  new class extends URLVerification {
        };

        $event_manager = $this->createMock(EventManager::class);
        $event_manager->expects(self::once())->method('processEvent')
            ->with('url_verification_instance', self::callback(function (array &$args) use ($url_verification_instance) {
                $args['url_verification'] = $url_verification_instance;
                return true;
            }));

        $urlVerif = new URLVerificationFactory($event_manager);

        self::assertSame($url_verification_instance, $urlVerif->getURLVerification([]));
    }
}
