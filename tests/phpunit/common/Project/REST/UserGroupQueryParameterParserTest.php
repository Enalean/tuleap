<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Project\REST;

use PHPUnit\Framework\TestCase;
use Tuleap\Project\REST\v1\UserGroupQueryParameterParser;
use Tuleap\REST\Exceptions\InvalidJsonException;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\JsonDecoder;

class UserGroupQueryParameterParserTest extends TestCase
{

    /**
     * @var UserGroupQueryParameterParser
     */
    private $parser;

    /**
     * @before
     */
    public function instanciateParser()
    {
        $this->parser = new UserGroupQueryParameterParser(new JsonDecoder());
    }

    public function testParseEmptyParameterReturnsRepresentationWithoutUserSystemGroup()
    {
        $representation = $this->parser->parse('');
        $this->assertFalse($representation->isWithSystemUserGroups());
    }

    public function testParseParameterWithUserSystemGroupReturnsRepresentationWithUserSystemGroup()
    {
        $representation = $this->parser->parse("{\"with_system_user_groups\": true}");
        $this->assertTrue($representation->isWithSystemUserGroups());
    }

    public function testParseParameterWithoutUserSystemGroupReturnsRepresentationWithoutUserSystemGroup()
    {
        $representation = $this->parser->parse("{\"with_system_user_groups\": false}");
        $this->assertFalse($representation->isWithSystemUserGroups());
    }

    public function testParseNotJsonParameterThrows400()
    {
        $this->expectException(InvalidJsonException::class);

        $this->parser->parse("not_json");
    }

    public function testParseJsonParameterWithoutMandatoryAttributeThrows400()
    {
        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);

        $this->parser->parse("{\"invalid_attribute\": false}");
    }
}
