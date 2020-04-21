<?php
/**
 * Copyright (c) Enalean, 2010 - present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2010
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

use PHPUnit\Framework\TestCase;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Docman_ErrorPermissionDeniedTest extends TestCase
{

    public function testUrlTransformMiddle(): void
    {
        $e   = new Docman_Error_PermissionDenied();
        $res = $e->urlTransform('https://example.com/plugins/docman/?group_id=1564&action=show&id=96739');
        $this->assertEquals(
            'https://example.com/plugins/docman/?group_id=1564&action=details&section=permissions&id=96739',
            $res
        );
    }

    public function testUrlTransformStart(): void
    {
        $e   = new Docman_Error_PermissionDenied();
        $res = $e->urlTransform('https://example.com/plugins/docman/?action=show&group_id=1564&id=96739');
        $this->assertEquals(
            'https://example.com/plugins/docman/?action=details&section=permissions&group_id=1564&id=96739',
            $res
        );
    }

    public function testUrlTransformEnd(): void
    {
        $e   = new Docman_Error_PermissionDenied();
        $res = $e->urlTransform('https://example.com/plugins/docman/?group_id=1564&id=96739&action=show');
        $this->assertEquals(
            'https://example.com/plugins/docman/?group_id=1564&id=96739&action=details&section=permissions',
            $res
        );
    }

    public function testUrlTransformWoAction(): void
    {
        $e   = new Docman_Error_PermissionDenied();
        $res = $e->urlTransform('https://example.com/plugins/docman/?group_id=1564&id=96739');
        $this->assertEquals(
            'https://example.com/plugins/docman/?group_id=1564&id=96739&action=details&section=permissions',
            $res
        );
    }

    public function testUrlTransformActionDetailsWoSection(): void
    {
        $e   = new Docman_Error_PermissionDenied();
        $res = $e->urlTransform('https://example.com/plugins/docman/?group_id=1564&id=96739&action=details');
        $this->assertEquals(
            'https://example.com/plugins/docman/?group_id=1564&id=96739&action=details&section=permissions',
            $res
        );
    }

    public function testUrlTransformActionDetailsSectionDifferentMiddle(): void
    {
        $e   = new Docman_Error_PermissionDenied();
        $res = $e->urlTransform(
            'https://example.com/plugins/docman/?group_id=1564&id=96739&section=pouet&action=details'
        );
        $this->assertEquals(
            'https://example.com/plugins/docman/?group_id=1564&id=96739&section=permissions&action=details',
            $res
        );
    }

    public function testUrlTransformActionDetailsSectionDifferentStart(): void
    {
        $e   = new Docman_Error_PermissionDenied();
        $res = $e->urlTransform(
            'https://example.com/plugins/docman/?section=pouet&group_id=1564&id=96739&action=details'
        );
        $this->assertEquals(
            'https://example.com/plugins/docman/?section=permissions&group_id=1564&id=96739&action=details',
            $res
        );
    }


    public function testUrlTransformActionDetailsSectionDifferentEnd(): void
    {
        $e   = new Docman_Error_PermissionDenied();
        $res = $e->urlTransform(
            'https://example.com/plugins/docman/?group_id=1564&id=96739&action=details&section=pouet'
        );
        $this->assertEquals(
            'https://example.com/plugins/docman/?group_id=1564&id=96739&action=details&section=permissions',
            $res
        );
    }


    public function testUrlQueryToArrayWithIdMiddle(): void
    {
        $e   = new Docman_Error_PermissionDenied();
        $res = $e->urlQueryToArray('https://example.com/plugins/docman/?group_id=1564&id=96739&action=show');
        $this->assertEquals(96739, $res['id']);
    }

    public function testUrlQueryToArrayWithIdStart(): void
    {
        $e   = new Docman_Error_PermissionDenied();
        $res = $e->urlQueryToArray('https://example.com/plugins/docman/?id=96739&group_id=1564&action=show');
        $this->assertEquals(96739, $res['id']);
    }

    public function testUrlQueryToArrayWithIdEnd(): void
    {
        $e   = new Docman_Error_PermissionDenied();
        $res = $e->urlQueryToArray('https://example.com/plugins/docman/?group_id=1564&action=show&id=96739');
        $this->assertEquals(96739, $res['id']);
    }

    public function testUrlQueryToArrayWithoutId(): void
    {
        $e   = new Docman_Error_PermissionDenied();
        $res = $e->urlQueryToArray('https://example.com/plugins/docman/?group_id=1564&action=show');
        $this->assertFalse(isset($res['id']));
    }
}
