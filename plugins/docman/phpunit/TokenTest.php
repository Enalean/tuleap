<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class TokenTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testGenerateRandomToken()
    {
        $dao  = \Mockery::spy(Docman_TokenDao::class);
        $http = \Mockery::spy(HTTPRequest::class);
        $http->allows()->get('bc')->andReturns(false);

        $t1 = \Mockery::mock(Docman_Token::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $t1->allows(['_getDao' => $dao]);
        $t1->allows(['_getReferer' => 'http://codendi.com/?id=1&action=show']);
        $t1->allows(['_getCurrentUserId' => '123']);
        $t1->allows(['_getHTTPRequest' => $http]);
        $t1->__construct();

        $t2 = \Mockery::mock(Docman_Token::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $t2->allows(['_getDao' => $dao]);
        $t2->allows(['_getReferer' => 'http://codendi.com/?id=1&action=show']);
        $t2->allows(['_getCurrentUserId' => '123']);
        $t2->allows(['_getHTTPRequest' => $http]);
        $t2->__construct();

        $t3 = \Mockery::mock(Docman_Token::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $t3->allows(['_getDao' => $dao]);
        $t3->allows(['_getReferer' => 'http://codendi.com/?id=2&action=show']);
        $t3->allows(['_getCurrentUserId' => '123']);
        $t3->allows(['_getHTTPRequest' => $http]);
        $t3->__construct();

        $t4 = \Mockery::mock(Docman_Token::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $t4->allows(['_getDao' => $dao]);
        $t4->allows(['_getReferer' => 'http://codendi.com/?id=1&action=show']);
        $t4->allows(['_getCurrentUserId' => '987']);
        $t4->allows(['_getHTTPRequest' => $http]);
        $t4->__construct();

        $this->assertNotEquals($t1->getToken(), $t2->getToken(), 'Same users, same referers, different tokens');
        $this->assertNotEquals($t1->getToken(), $t3->getToken(), 'Different referers, different tokens');
        $this->assertNotEquals($t1->getToken(), $t4->getToken(), 'Different users, different tokens');
    }

    public function testNullToken()
    {
        $dao  = \Mockery::spy(Docman_TokenDao::class);
        $http = \Mockery::spy(HTTPRequest::class);
        $http->allows()->get('bc')->andReturns(false);

        $t1 = \Mockery::mock(Docman_Token::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $t1->allows(['_getDao' => $dao]);
        $t1->allows(['_getReferer' => 'http://codendi.com/?']);
        $t1->allows(['_getCurrentUserId' => '123']);
        $t1->allows(['_getHTTPRequest' => $http]);
        $t1->__construct();

        $this->assertNull($t1->getToken(), 'Without referer, we should have a null token');

        $t2 = \Mockery::mock(Docman_Token::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $t2->allows(['_getDao' => $dao]);
        $t2->allows(['_getReferer' => 'http://codendi.com/?id=1&action=show']);
        $t2->allows(['_getCurrentUserId' => '123']);
        $t2->allows(['_getHTTPRequest' => $http]);
        $t2->__construct();

        $this->assertNotNull($t2->getToken());

        $t3 = \Mockery::mock(Docman_Token::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $t3->allows(['_getDao' => $dao]);
        $t3->allows(['_getReferer' => 'http://codendi.com/?id=1&action=show']);
        $t3->allows(['_getCurrentUserId' => null]);
        $t3->allows(['_getHTTPRequest' => $http]);
        $t3->__construct();

        $this->assertNull($t3->getToken(), 'With anonymous user, we should have a null token');
    }

    public function testStorage()
    {
        $user_id = 123;
        $referer = 'http://codendi.com/?id=1&action=show';

        $dao = \Mockery::spy(Docman_TokenDao::class);
        $dao->expects()->create($user_id, \Mockery::any(), $referer);
        $http = \Mockery::spy(HTTPRequest::class);
        $http->allows()->get('bc')->andReturns(false);

        $t1 = \Mockery::mock(Docman_Token::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $t1->allows(['_getDao' => $dao]);
        $t1->allows(['_getReferer' => $referer]);
        $t1->allows(['_getCurrentUserId' => $user_id]);
        $t1->allows(['_getHTTPRequest' => $http]);
        $t1->__construct();
    }

    public function testInvalidReferer()
    {
        $dao  = \Mockery::spy(Docman_TokenDao::class);
        $http = \Mockery::spy(HTTPRequest::class);
        $http->allows()->get('bc')->andReturns(false);
        foreach (array('aaaa', '?action=foo', '?action=details&section=notification') as $referer) {
            $t = \Mockery::mock(Docman_Token::class)->makePartial()->shouldAllowMockingProtectedMethods();
            $t->allows(['_getDao' => $dao]);
            $t->allows(['_getReferer' => 'http://codendi.com/' . $referer]);
            $t->allows(['_getCurrentUserId' => '123']);
            $t->allows(['_getHTTPRequest' => $http]);
            $t->__construct();

            $this->assertNull($t->getToken(), 'Without valid referer, we should have a null token');
        }
        foreach (array(
                     '?action=show',
                     '?id=1&action=show',
                     '?action=details',
                     '?action=details&section=history'
                 ) as $referer) {
            $t = \Mockery::mock(Docman_Token::class)->makePartial()->shouldAllowMockingProtectedMethods();
            $t->allows(['_getDao' => $dao]);
            $t->allows(['_getReferer' => 'http://codendi.com/' . $referer]);
            $t->allows(['_getCurrentUserId' => '123']);
            $t->allows(['_getHTTPRequest' => $http]);
            $t->__construct();

            $this->assertNotNull($t->getToken(), "With valid referer, we should'nt have a null token");
        }
    }
}
