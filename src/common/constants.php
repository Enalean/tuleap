<?php
/*
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, If not, see <http://www.gnu.org/licenses/>
 */

/* Global constants are evil, please avoid them whenever possible.
 * Use class constants instead.
 */
define('CODENDI_PURIFIER_CONVERT_HTML', Codendi_HTMLPurifier::CONFIG_CONVERT_HTML);
define('CODENDI_PURIFIER_STRIP_HTML', Codendi_HTMLPurifier::CONFIG_STRIP_HTML);
define('CODENDI_PURIFIER_BASIC', Codendi_HTMLPurifier::CONFIG_BASIC);
define('CODENDI_PURIFIER_BASIC_NOBR', Codendi_HTMLPurifier::CONFIG_BASIC_NOBR);
define('CODENDI_PURIFIER_LIGHT', Codendi_HTMLPurifier::CONFIG_LIGHT);
define('CODENDI_PURIFIER_FULL', Codendi_HTMLPurifier::CONFIG_FULL);
define('CODENDI_PURIFIER_JS_QUOTE', Codendi_HTMLPurifier::CONFIG_JS_QUOTE);
define('CODENDI_PURIFIER_JS_DQUOTE', Codendi_HTMLPurifier::CONFIG_JS_DQUOTE);
define('CODENDI_PURIFIER_DISABLED', Codendi_HTMLPurifier::CONFIG_DISABLED);
