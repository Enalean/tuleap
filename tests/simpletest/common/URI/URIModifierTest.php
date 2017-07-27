<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\URI;

class URIModifierTest extends \TuleapTestCase
{
    public function itRemovesDotSegmentsOfRelativeURIs()
    {
        $this->assertEqual('', URIModifier::removeDotSegments(''));
        $this->assertEqual('a/', URIModifier::removeDotSegments('a/'));
        $this->assertEqual('a/b', URIModifier::removeDotSegments('a/b'));
        $this->assertEqual('mid/6', URIModifier::removeDotSegments('mid/content=5/../6'));
        $this->assertEqual('g', URIModifier::removeDotSegments('a/b/c/./../../../../g'));
        $this->assertEqual('a/b/c/', URIModifier::removeDotSegments('a/b/././././c/././.'));
        $this->assertEqual('/', URIModifier::removeDotSegments('.'));
        $this->assertEqual('/', URIModifier::removeDotSegments('..'));
    }

    public function itRemovesDotSegmentsOfAbsoluteURIs()
    {
        $this->assertEqual('/', URIModifier::removeDotSegments('/'));
        $this->assertEqual('/', URIModifier::removeDotSegments('/'));
        $this->assertEqual('/a/g', URIModifier::removeDotSegments('/a/b/c/./../../g'));
        $this->assertEqual('g', URIModifier::removeDotSegments('/a/b/c/./../../../../../../g'));
        $this->assertEqual('/', URIModifier::removeDotSegments('/.'));
        $this->assertEqual('/', URIModifier::removeDotSegments('/..'));
    }

    public function itNormalizesPercentEncoding()
    {
        $this->assertEqual('/a/b', URIModifier::normalizePercentEncoding('/a/b'));
        $this->assertEqual('/%5B%5D/a', URIModifier::normalizePercentEncoding('/[]/a'));
    }
}
