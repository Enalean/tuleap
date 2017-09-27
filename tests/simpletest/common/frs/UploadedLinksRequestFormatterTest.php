<?php
/**
 *  Copyright (c) Enalean, 2017. All Rights Reserved.
 *
 *   This file is a part of Tuleap.
 *
 *   Tuleap is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 *   Tuleap is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\FRS;

use TuleapTestCase;

class UploadedLinksRequestFormatterTest extends TuleapTestCase
{
    public function itExtractsOneArrayFromLinksProvidedInRequest()
    {
        $request = mock('HTTPRequest');
        stub($request)->get('uploaded-link-name')->returns(array('test', ''));
        stub($request)->get('uploaded-link')->returns(array('http://example.com', 'ftp://example.com'));
        stub($request)->validArray()->returns(true);

        $formatter      = new UploadedLinksRequestFormatter();
        $expected_links = array(
            array('link' => 'http://example.com', 'name' => 'test'),
            array('link' => 'ftp://example.com', 'name' => '')
        );

        $this->assertEqual($expected_links, $formatter->formatFromRequest($request));
    }

    public function itThrowsAnExceptionWhenRequestDoesNotProvideCorrectInput()
    {
        $request = mock('HTTPRequest');
        stub($request)->get('uploaded-link-name')->returns(array('test'));
        stub($request)->get('uploaded-link')->returns(array('http://example.com', 'https://example.com'));
        stub($request)->validArray()->returns(true);

        $this->expectException('Tuleap\FRS\UploadedLinksInvalidFormException');
        $formatter = new UploadedLinksRequestFormatter();
        $formatter->formatFromRequest($request);
    }

    public function itDoesNotAcceptInvalidLinks()
    {
        $request = mock('HTTPRequest');
        stub($request)->get('uploaded-link-name')->returns(array('invalid'));
        stub($request)->get('uploaded-link')->returns(array('example.com'));
        stub($request)->validArray()->returns(true);

        $formatter = new UploadedLinksRequestFormatter();

        $this->expectException('Tuleap\FRS\UploadedLinksInvalidFormException');
        $formatter->formatFromRequest($request);
    }

    public function itDoesNotEmptyLinks()
    {
        $request = mock('HTTPRequest');
        stub($request)->get('uploaded-link-name')->returns(array());
        stub($request)->get('uploaded-link')->returns(array());
        stub($request)->validArray()->returns(true);

        $formatter      = new UploadedLinksRequestFormatter();
        $expected_links = array();

        $this->assertEqual($expected_links, $formatter->formatFromRequest($request));
    }
}
