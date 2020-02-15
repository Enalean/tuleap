<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'SOAP_RequestLimitator.class.php';
require_once 'dao/SOAP_RequestLimitatorDao.class.php';

/**
 * Create a SOAP_Limitator wired to the database and configured according to
 * the configuration (local.inc)
 */
class SOAP_RequestLimitatorFactory
{

    /**
     * Returns a Limitator object
     *
     * @return SOAP_RequestLimitator
     */
    public function getLimitator()
    {
        return new SOAP_RequestLimitator(ForgeConfig::get('sys_nb_sensitive_soap_calls_per_hour'), 3600, new SOAP_RequestLimitatorDao());
    }
}
