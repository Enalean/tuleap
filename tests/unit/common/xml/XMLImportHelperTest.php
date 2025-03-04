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

use PFUser;
use SimpleXMLElement;
use Tuleap\GlobalLanguageMock;
use XMLImportHelper;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class XMLImportHelperTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    public function testItImportsAnonymousUser(): void
    {
        $user_manager  = $this->createMock(\UserManager::class);
        $import_helper = new XMLImportHelper($user_manager);
        $user_manager->method('getUserByIdentifier')->willReturn(null);
        $user_manager->method('getUserAnonymous')->willReturn(new PFUser());

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
<user>veloc@example.com</user>');

        $user = $import_helper->getUser($xml);

        self::assertEquals('veloc@example.com', $user->getEmail());
    }
}
