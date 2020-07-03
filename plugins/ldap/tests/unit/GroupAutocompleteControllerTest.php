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

require_once __DIR__ . '/bootstrap.php';

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Layout\BaseLayout;

final class GroupAutocompleteControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var GroupAutocompleteController
     */
    private $group_autocomplete;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|BaseLayout
     */
    private $layout;
    /**
     * @var \LDAP|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $ldap;
    /**
     * @var \HttpRequest|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $request;

    protected function setUp(): void
    {
        $this->request = \Mockery::mock(\HTTPRequest::class);
        $this->ldap    = \Mockery::mock(\LDAP::class);
        $this->layout  = \Mockery::mock(BaseLayout::class);

        $this->group_autocomplete = new GroupAutocompleteController($this->ldap);
    }

    public function testItReturnsAndEmptyObjectWhenLdapGroupNameIsNotFound(): void
    {
        $this->request->shouldReceive('valid')->andReturnFalse();

        $output = [
            'results'    => [],
            'pagination' => [
                'more' => false
            ]
        ];
        $this->layout->shouldReceive('sendJSON')->with($output);

        $this->group_autocomplete->process($this->request, $this->layout, []);
    }

    public function testItReturnsAndEmptyObjectWhenRequestDoesNotMatchLdapGroupName(): void
    {
        $this->request->shouldReceive('valid')->andReturnTrue();
        $this->request->shouldReceive('get')->with('ldap_group_name')->andReturn('gn');
        $this->ldap->shouldReceive('searchGroupAsYouType')->andReturnFalse();
        $output = [
            'results'    => [],
            'pagination' => [
                'more' => false
            ]
        ];
        $this->layout->shouldReceive('sendJSON')->with($output);


        $this->group_autocomplete->process($this->request, $this->layout, []);
    }

    public function testItAutoCompletesGroups(): void
    {
        $this->request->shouldReceive('valid')->andReturnTrue();
        $this->request->shouldReceive('get')->with('ldap_group_name')->andReturn('gn');

        $ldap_iterator = \Mockery::spy(\LDAPResultIterator::class);
        $ldap_iterator->shouldReceive('count')->andReturns(1);
        $ldap_iterator->shouldReceive('valid')->andReturns(true, false);

        $res = \Mockery::spy(\LDAPResult::class);
        $res->shouldReceive('getGroupCommonName')->andReturns('mis_1234');
        $res->shouldReceive('getGroupDisplayName')->andReturns('test_group_dn');

        $ldap_iterator->shouldReceive('current')->andReturns($res);

        $this->ldap->shouldReceive('searchGroupAsYouType')->andReturn($ldap_iterator);
        $this->ldap->shouldReceive('getErrno')->andReturns(\LDAP::ERR_SUCCESS);

        $output = [
            'results'    =>
                [
                    [
                        'id'   => 'mis_1234',
                        'text' => 'test_group_dn',
                    ]
                ],
            'pagination' => ['more' => false],
        ];
        $this->layout->shouldReceive('sendJSON')->with($output);

        $this->group_autocomplete->process($this->request, $this->layout, []);
    }
}
