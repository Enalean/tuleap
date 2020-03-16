<?php
/**
 * Copyright (c) Enalean, 2015-2019. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

require_once('bootstrap.php');

abstract class Tracker_FormElement_Field_File_BaseTest extends TuleapTestCase
{
    protected $fixture_dir;
    protected $attachment_dir;
    protected $thumbnails_dir;
    protected $tmp_name;
    protected $another_tmp_name;
    protected $file_info_factory;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        ForgeConfig::store();
        $this->fixture_dir    = '/var/tmp' . '/_fixtures';
        if (!is_dir($this->fixture_dir)) {
            mkdir($this->fixture_dir);
        }

        $this->attachment_dir = $this->fixture_dir . '/attachments';
        if (!is_dir($this->attachment_dir)) {
            mkdir($this->attachment_dir);
        }

        $this->thumbnails_dir = $this->attachment_dir . '/thumbnails';
        if (!is_dir($this->thumbnails_dir)) {
            mkdir($this->thumbnails_dir);
        }

        $this->tmp_name         = $this->fixture_dir . '/uploaded_file.txt';
        $this->another_tmp_name = $this->fixture_dir . '/another_uploaded_file.txt';

        $this->file_info_factory = \Mockery::spy(\Tracker_FileInfoFactory::class);

        ForgeConfig::set('sys_http_user', 'user');

        $backend = \Mockery::spy(\Backend::class);
        Backend::setInstance('Backend', $backend);
    }

    public function tearDown()
    {
        foreach (glob($this->thumbnails_dir . '/*') as $f) {
            if ($f != '.' && $f != '..') {
                unlink($f);
            }
        }
        rmdir($this->thumbnails_dir);
        ForgeConfig::restore();
        Backend::clearInstances();

        parent::tearDown();
    }
}

class Tracker_FormElement_Field_File_RESTTests extends TuleapTestCase
{

    public function itThrowsAnExceptionWhenReturningValueIndexedByFieldName()
    {
        $field = new Tracker_FormElement_Field_File(
            1,
            101,
            null,
            'field_file',
            'Field File',
            '',
            1,
            'P',
            true,
            '',
            1
        );

        $this->expectException('Tracker_FormElement_RESTValueByField_NotImplementedException');

        $value = 'some_value';

        $field->getFieldDataFromRESTValueByField($value);
    }
}
