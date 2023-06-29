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

namespace Tuleap\LDAP;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Layout\BaseLayout;

final class GroupAutocompleteControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private GroupAutocompleteController $group_autocomplete;
    private MockObject&BaseLayout $layout;
    private \LDAP&MockObject $ldap;
    private \HTTPRequest&MockObject $request;

    protected function setUp(): void
    {
        $this->request = $this->createMock(\HTTPRequest::class);
        $this->ldap    = $this->createMock(\LDAP::class);
        $this->layout  = $this->createMock(BaseLayout::class);

        $this->group_autocomplete = new GroupAutocompleteController($this->ldap);
    }

    public function testItReturnsAndEmptyObjectWhenLdapGroupNameIsNotFound(): void
    {
        $this->request->method('valid')->willReturn(false);

        $output = [
            'results'    => [],
            'pagination' => [
                'more' => false,
            ],
        ];
        $this->layout->expects(self::atLeast(1))->method('sendJSON')->with($output);

        $this->group_autocomplete->process($this->request, $this->layout, []);
    }

    public function testItReturnsAndEmptyObjectWhenRequestDoesNotMatchLdapGroupName(): void
    {
        $this->request->method('valid')->willReturn(true);
        $this->request->method('get')->with('ldap_group_name')->willReturn('gn');
        $this->ldap->method('searchGroupAsYouType')->willReturn(false);
        $output = [
            'results'    => [],
            'pagination' => [
                'more' => false,
            ],
        ];
        $this->layout->expects(self::atLeast(1))->method('sendJSON')->with($output);


        $this->group_autocomplete->process($this->request, $this->layout, []);
    }

    public function testItAutoCompletesGroups(): void
    {
        $this->request->method('valid')->willReturn(true);
        $this->request->method('get')->with('ldap_group_name')->willReturn('gn');

        $ldap_iterator = $this->createMock(\LDAPResultIterator::class);
        $ldap_iterator->method('count')->willReturn(1);
        $ldap_iterator->method('valid')->willReturn(true, false);

        $res = $this->createMock(\LDAPResult::class);
        $res->method('getGroupCommonName')->willReturn('mis_1234');
        $res->method('getGroupDisplayName')->willReturn('test_group_dn');

        $ldap_iterator->method('current')->willReturn($res);
        $ldap_iterator->method('next');

        $this->ldap->method('searchGroupAsYouType')->willReturn($ldap_iterator);
        $this->ldap->method('getErrno')->willReturn(\LDAP::ERR_SUCCESS);

        $output = [
            'results'    =>
                [
                    [
                        'id'   => 'mis_1234',
                        'text' => 'test_group_dn',
                    ],
                ],
            'pagination' => ['more' => false],
        ];
        $this->layout->expects(self::atLeast(1))->method('sendJSON')->with($output);

        $this->group_autocomplete->process($this->request, $this->layout, []);
    }
}
