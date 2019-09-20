<?php
/*
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2010
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once 'bootstrap.php';

class Docman_ErrorPermissionDeniedTest extends TuleapTestCase
{

    function testUrlTransformMiddle()
    {
        $e   = new Docman_Error_PermissionDenied();
        $res = $e->urlTransform('https://codendi.org/plugins/docman/?group_id=1564&action=show&id=96739');
        $this->assertEqual($res, 'https://codendi.org/plugins/docman/?group_id=1564&action=details&section=permissions&id=96739');
    }

    function testUrlTransformStart()
    {
        $e   = new Docman_Error_PermissionDenied();
        $res = $e->urlTransform('https://codendi.org/plugins/docman/?action=show&group_id=1564&id=96739');
        $this->assertEqual($res, 'https://codendi.org/plugins/docman/?action=details&section=permissions&group_id=1564&id=96739');
    }

    function testUrlTransformEnd()
    {
        $e   = new Docman_Error_PermissionDenied();
        $res = $e->urlTransform('https://codendi.org/plugins/docman/?group_id=1564&id=96739&action=show');
        $this->assertEqual($res, 'https://codendi.org/plugins/docman/?group_id=1564&id=96739&action=details&section=permissions');
    }

    function testUrlTransformWoAction()
    {
        $e   = new Docman_Error_PermissionDenied();
        $res = $e->urlTransform('https://codendi.org/plugins/docman/?group_id=1564&id=96739');
        $this->assertEqual($res, 'https://codendi.org/plugins/docman/?group_id=1564&id=96739&action=details&section=permissions');
    }

    function testUrlTransformActionDetailsWoSection()
    {
        $e   = new Docman_Error_PermissionDenied();
        $res = $e->urlTransform('https://codendi.org/plugins/docman/?group_id=1564&id=96739&action=details');
        $this->assertEqual($res, 'https://codendi.org/plugins/docman/?group_id=1564&id=96739&action=details&section=permissions');
    }

    function testUrlTransformActionDetailsSectionDifferentMiddle()
    {
        $e   = new Docman_Error_PermissionDenied();
        $res = $e->urlTransform('https://codendi.org/plugins/docman/?group_id=1564&id=96739&section=pouet&action=details');
        $this->assertEqual($res, 'https://codendi.org/plugins/docman/?group_id=1564&id=96739&section=permissions&action=details');
    }

    function testUrlTransformActionDetailsSectionDifferentStart()
    {
        $e   = new Docman_Error_PermissionDenied();
        $res = $e->urlTransform('https://codendi.org/plugins/docman/?section=pouet&group_id=1564&id=96739&action=details');
        $this->assertEqual($res, 'https://codendi.org/plugins/docman/?section=permissions&group_id=1564&id=96739&action=details');
    }


    function testUrlTransformActionDetailsSectionDifferentEnd()
    {
        $e   = new Docman_Error_PermissionDenied();
        $res = $e->urlTransform('https://codendi.org/plugins/docman/?group_id=1564&id=96739&action=details&section=pouet');
        $this->assertEqual($res, 'https://codendi.org/plugins/docman/?group_id=1564&id=96739&action=details&section=permissions');
    }




    function testUrlQueryToArrayWithIdMiddle()
    {
        $e   = new Docman_Error_PermissionDenied();
        $res = $e-> urlQueryToArray('https://codendi.org/plugins/docman/?group_id=1564&id=96739&action=show');
        $this->assertEqual($res['id'], 96739);
    }

    function testUrlQueryToArrayWithIdStart()
    {
        $e   = new Docman_Error_PermissionDenied();
        $res = $e-> urlQueryToArray('https://codendi.org/plugins/docman/?id=96739&group_id=1564&action=show');
        $this->assertEqual($res['id'], 96739);
    }

    function testUrlQueryToArrayWithIdEnd()
    {
        $e   = new Docman_Error_PermissionDenied();
        $res = $e-> urlQueryToArray('https://codendi.org/plugins/docman/?group_id=1564&action=show&id=96739');
        $this->assertEqual($res['id'], 96739);
    }

    function testUrlQueryToArrayWithoutId()
    {
        $e   = new Docman_Error_PermissionDenied();
        $res = $e-> urlQueryToArray('https://codendi.org/plugins/docman/?group_id=1564&action=show');
        $this->assertFalse(isset($res['id']));
    }
}
