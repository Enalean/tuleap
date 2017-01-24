<?php
/**
 * Copyright (c) Xerox, 2006. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2006.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 *
 */

require_once 'bootstrap.php';

Mock::generatePartial('Docman_Token', 'Docman_TokenTestVersion',
    array(
        '_getDao',
        '_getReferer',
        '_getCurrentUserId',
        '_getHTTPRequest',
    )
);

Mock::generate('Docman_TokenDao');

Mock::generate('UserManager');
Mock::generate('PFUser');
Mock::generate('DataAccessResult');
Mock::generate('HTTPRequest');

class TokenTest extends TuleapTestCase {

    function testGenerateRandomToken() {
        $dao  = new MockDocman_TokenDao();
        $http = new MockHTTPRequest();
        $http->setReturnValue('get', false, array('bc'));

        $t1 = new Docman_TokenTestVersion();
        $t1->setReturnReference('_getDao', $dao);
        $t1->setReturnValue('_getReferer', 'http://codendi.com/?id=1&action=show');
        $t1->setReturnValue('_getCurrentUserId', '123');
        $t1->setReturnValue('_getHTTPRequest', $http);
        $t1->Docman_Token();

        $t2 = new Docman_TokenTestVersion();
        $t2->setReturnReference('_getDao', $dao);
        $t2->setReturnValue('_getReferer', 'http://codendi.com/?id=1&action=show');
        $t2->setReturnValue('_getCurrentUserId', '123');
        $t2->setReturnValue('_getHTTPRequest', $http);
        $t2->Docman_Token();

        $t3 = new Docman_TokenTestVersion();
        $t3->setReturnReference('_getDao', $dao);
        $t3->setReturnValue('_getReferer', 'http://codendi.com/?id=2&action=show');
        $t3->setReturnValue('_getCurrentUserId', '123');
        $t3->setReturnValue('_getHTTPRequest', $http);
        $t3->Docman_Token();

        $t4 = new Docman_TokenTestVersion();
        $t4->setReturnReference('_getDao', $dao);
        $t4->setReturnValue('_getReferer', 'http://codendi.com/?id=1&action=show');
        $t4->setReturnValue('_getCurrentUserId', '987');
        $t4->setReturnValue('_getHTTPRequest', $http);
        $t4->Docman_Token();

        $this->assertNotEqual($t1->getToken(), $t2->getToken(), 'Same users, same referers, different tokens');
        $this->assertNotEqual($t1->getToken(), $t3->getToken(), 'Different referers, different tokens');
        $this->assertNotEqual($t1->getToken(), $t4->getToken(), 'Different users, different tokens');
    }
    function testNullToken() {
        $dao  = new MockDocman_TokenDao();
        $http = new MockHTTPRequest();
        $http->setReturnValue('get', false, array('bc'));

        $t1 = new Docman_TokenTestVersion();
        $t1->setReturnReference('_getDao', $dao);
        $t1->setReturnValue('_getReferer', 'http://codendi.com/?');
        $t1->setReturnValue('_getCurrentUserId', '123');
        $t1->setReturnValue('_getHTTPRequest', $http);
        $t1->Docman_Token();

        $this->assertNull($t1->getToken(), 'Without referer, we should have a null token');


        $t2 = new Docman_TokenTestVersion();
        $t2->setReturnReference('_getDao', $dao);
        $t2->setReturnValue('_getReferer', 'http://codendi.com/?id=1&action=show');
        $t2->setReturnValue('_getCurrentUserId', '123');
        $t2->setReturnValue('_getHTTPRequest', $http);
        $t2->Docman_Token();

        $this->assertNotNull($t2->getToken());


        $t3 = new Docman_TokenTestVersion();
        $t3->setReturnReference('_getDao', $dao);
        $t3->setReturnValue('_getReferer', 'http://codendi.com/?id=1&action=show');
        $t3->setReturnValue('_getCurrentUserId', null);
        $t3->setReturnValue('_getHTTPRequest', $http);
        $t3->Docman_Token();

        $this->assertNull($t3->getToken(), 'With anonymous user, we should have a null token');
    }

    function testStorage() {
        $user_id = 123;
        $referer = 'http://codendi.com/?id=1&action=show';

        $dao = new MockDocman_TokenDao();
        $dao->expectOnce('create', array($user_id, '*', $referer));
        $http = new MockHTTPRequest();
        $http->setReturnValue('get', false, array('bc'));

        $t1 = new Docman_TokenTestVersion();
        $t1->setReturnReference('_getDao', $dao);
        $t1->setReturnValue('_getReferer', $referer);
        $t1->setReturnValue('_getCurrentUserId', $user_id);
        $t1->setReturnValue('_getHTTPRequest', $http);
        $t1->Docman_Token();
    }

    function testInvalidReferer() {
        $dao  = new MockDocman_TokenDao();
        $http = new MockHTTPRequest();
        $http->setReturnValue('get', false, array('bc'));
        foreach(array('aaaa', '?action=foo', '?action=details&section=notification') as $referer) {
            $t = new Docman_TokenTestVersion();
            $t->setReturnReference('_getDao', $dao);
            $t->setReturnValue('_getReferer', 'http://codendi.com/'. $referer);
            $t->setReturnValue('_getCurrentUserId', '123');
            $t->setReturnValue('_getHTTPRequest', $http);
            $t->Docman_Token();

            $this->assertNull($t->getToken(), 'Without valid referer, we should have a null token');
        }
        foreach(array('?action=show', '?id=1&action=show', '?action=details', '?action=details&section=history') as $referer) {
            $t = new Docman_TokenTestVersion();
            $t->setReturnReference('_getDao', $dao);
            $t->setReturnValue('_getReferer', 'http://codendi.com/'. $referer);
            $t->setReturnValue('_getCurrentUserId', '123');
            $t->setReturnValue('_getHTTPRequest', $http);
            $t->Docman_Token();

            $this->assertNotNull($t->getToken(), "With valid referer, we should'nt have a null token");
        }
    }
}
