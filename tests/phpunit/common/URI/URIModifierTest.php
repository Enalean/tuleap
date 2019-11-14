<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\URI;

use PHPUnit\Framework\TestCase;

class URIModifierTest extends TestCase
{
    public function testItRemovesDotSegmentsOfRelativeURIs(): void
    {
        $this->assertEquals('', URIModifier::removeDotSegments(''));
        $this->assertEquals('a/', URIModifier::removeDotSegments('a/'));
        $this->assertEquals('a/b', URIModifier::removeDotSegments('a/b'));
        $this->assertEquals('mid/6', URIModifier::removeDotSegments('mid/content=5/../6'));
        $this->assertEquals('g', URIModifier::removeDotSegments('a/b/c/./../../../../g'));
        $this->assertEquals('a/b/c/', URIModifier::removeDotSegments('a/b/././././c/././.'));
        $this->assertEquals('/', URIModifier::removeDotSegments('.'));
        $this->assertEquals('/', URIModifier::removeDotSegments('..'));
    }

    public function testItRemovesDotSegmentsOfAbsoluteURIs(): void
    {
        $this->assertEquals('/', URIModifier::removeDotSegments('/'));
        $this->assertEquals('/', URIModifier::removeDotSegments('/'));
        $this->assertEquals('/a/g', URIModifier::removeDotSegments('/a/b/c/./../../g'));
        $this->assertEquals('g', URIModifier::removeDotSegments('/a/b/c/./../../../../../../g'));
        $this->assertEquals('/', URIModifier::removeDotSegments('/.'));
        $this->assertEquals('/', URIModifier::removeDotSegments('/..'));
    }

    public function testItNormalizesPercentEncoding(): void
    {
        $this->assertEquals('/a/b', URIModifier::normalizePercentEncoding('/a/b'));
        $this->assertEquals('/%5B%5D/a', URIModifier::normalizePercentEncoding('/[]/a'));
    }

    public function testItRemovesEmptySegments(): void
    {
        $this->assertEquals('/a/b/c/', URIModifier::removeEmptySegments('/a//b////c/'));
        $this->assertEquals('/a/../b/', URIModifier::removeEmptySegments('/a//..//b//'));
    }

    public function testItDoesUpdateVfsStreamSchemePath(): void
    {
        $this->assertEquals('vfs://root/path', URIModifier::removeEmptySegments('vfs://root//path'));
        $this->assertEquals('/root/path', URIModifier::removeEmptySegments('//root//path'));
        $this->assertEquals('/root/path/vfs:/riri', URIModifier::removeEmptySegments('/root/path/vfs://riri'));
    }
}
