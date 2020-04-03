<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

namespace Tuleap\XML;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Tuleap\GlobalLanguageMock;
use XMLImportHelper;

class XMLImportHelperTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    public function testItImportsAnonymousUser()
    {
        $user_manager  = \Mockery::spy(\UserManager::class);
        $import_helper = new XMLImportHelper($user_manager);
        $user_manager->shouldReceive('getUserByIdentifier')->andReturns(null);
        $user_manager->shouldReceive('getUserAnonymous')->andReturns(new PFUser());

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
<user>veloc@example.com</user>');

        $user = $import_helper->getUser($xml);

        $this->assertEquals('veloc@example.com', $user->getEmail());
    }
}
