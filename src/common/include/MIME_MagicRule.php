<?php
/**
 * MIME.php, provides functions for determining MIME types and getting info about MIME types
 * Copyright (C) 2003 Arend van Beelen, Auton Rijnsburg. arend@auton.nl
 *
 * Updated for Codendi by Nicolas Terray 2008
 *
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * helper class for MIME::type()
 */
class MIME_MagicRule // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public $start_offset;
    public $value;
    public $mask;
    public $word_size;
    public $range_length;
    public $children;
}
