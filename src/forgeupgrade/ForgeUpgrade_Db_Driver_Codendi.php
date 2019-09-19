<?php
/**
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

require_once 'ForgeUpgrade_Db_Driver.php';

class ForgeUpgrade_Db_Driver_Codendi extends ForgeUpgrade_Db_Driver
{
    protected $pdo;
    protected $dsn;
    protected $user;
    protected $password;

    protected $platform_name = "codendi";
    protected $env_variable_name = "CODENDI_LOCAL_INC";
}
