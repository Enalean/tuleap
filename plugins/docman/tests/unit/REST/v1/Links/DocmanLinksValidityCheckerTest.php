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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Docman\REST\v1;

use Luracast\Restler\RestException;
use PHPUnit\Framework\TestCase;
use Tuleap\Docman\REST\v1\Links\DocmanLinksValidityChecker;

class DocmanLinksValidityCheckerTest extends TestCase
{
    /**
     * @var DocmanLinksValidityChecker
     */
    private $checker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->checker = new DocmanLinksValidityChecker();
    }

    public function testLinkIsValidInHTTPFormat(): void
    {
        $link_url = "http://example.com";
        $this->checker->checkLinkValidity($link_url);
        $this->addToAssertionCount(1);
    }

    public function testLinkIsValidInHTTPSFormat(): void
    {
        $link_url = "https://example.com";
        $this->checker->checkLinkValidity($link_url);
        $this->addToAssertionCount(1);
    }

    public function testLinkIsValidInFTPFormat(): void
    {
        $link_url = "ftp://example.com";
        $this->checker->checkLinkValidity($link_url);
        $this->addToAssertionCount(1);
    }

    public function testLinkIsValidInFTPsFormat(): void
    {
        $link_url = "ftps://example.com";
        $this->checker->checkLinkValidity($link_url);
        $this->addToAssertionCount(1);
    }


    public function testLinkIsNotValid(): void
    {
        $this->expectException(RestException::class);
        $link_url = "example.com";
        $this->checker->checkLinkValidity($link_url);
    }
}
