<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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

/* abstract */ class Docman_View_View //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
{
    /**
     * @var Docman_Controller
     */
    public $_controller; //phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

    /**
     * @var Codendi_HTMLPurifier
     */
    public $hp;

    public function __construct(&$controller)
    {
        $this->_controller = $controller;
        $this->hp          = Codendi_HTMLPurifier::instance();
    }

    public function display($params = [])
    {
        echo '<div class="docman-content">';
        $this->_content($params);
        echo '</div>';
    }

    /* protected */ public function _content($params)//phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
    }
}
