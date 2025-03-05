<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

namespace Tuleap\User\XML;

use PFUser;
use XML_RNGValidator;
use XML_SimpleXMLCDATAFactory;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserXMLExportedDevNullCollectionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItIgnoresUsersAddedToTheCollection(): void
    {
        $collection = new UserXMLExportedDevNullCollection(
            new XML_RNGValidator(),
            new XML_SimpleXMLCDATAFactory()
        );

        $collection->add(
            new PFUser(
                [
                    'user_id'     => 101,
                    'user_name'   => 'kshen',
                    'realname'    => 'Kool Shen',
                    'email'       => 'kshen@hotmail.fr',
                    'ldap_id'     => 'cb9867',
                    'language_id' => 'en',
                ]
            )
        );
        $collection->add(
            new PFUser(
                [
                    'user_id'     => 102,
                    'user_name'   => 'jstar',
                    'realname'    => 'Joeystarr <script>',
                    'email'       => 'jstar@caramail.com',
                    'language_id' => 'en',
                ]
            )
        );

        $xml_content = $collection->toXML();
        $xml_object  = simplexml_load_string($xml_content);

        self::assertCount(0, $xml_object->user);
    }
}
