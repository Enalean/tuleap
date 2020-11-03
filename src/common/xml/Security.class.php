<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

/**
 * @deprecated
 */
class XML_Security
{

    public function enableExternalLoadOfEntities()
    {
        return $this->setExternalLoadOfEntities(false);
    }

    /**
     * Prevent XXE attacks
     *
     * Important fact:
     * * not thread safe (php-fpm)
     *
     * Useful links:
     * https://www.owasp.org/index.php/XML_External_Entity_%28XXE%29_Processing
     * https://bugs.php.net/bug.php?id=64938
     *
     * @return The previous value
     */
    public function disableExternalLoadOfEntities()
    {
        return $this->setExternalLoadOfEntities(true);
    }

    private function setExternalLoadOfEntities($value)
    {
        return libxml_disable_entity_loader($value);
    }
}
