<?php
/**
 * Copyright (c) Enalean SAS, 2011-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet
 *
 * ForgeUpgrade is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * ForgeUpgrade is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with ForgeUpgrade. If not, see <http://www.gnu.org/licenses/>.
 */

abstract class ForgeUpgrade_Db_Driver_Abstract
{

    /**
     * Setup the PDO object to be used for DB connexion
     *
     * The DB connexion will be used to store buckets execution log.
     *
     * @return PDO
     */
    abstract public function getPdo();

    /**
     * Return a PDO logger appender that will reference the given bucket id
     *
     * @param ForgeUpgrade_Bucket $bucket The bucket
     *
     * @return LoggerAppender
     */
    abstract public function getBucketLoggerAppender(ForgeUpgrade_Bucket $bucket);
}
