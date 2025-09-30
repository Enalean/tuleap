<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
class Docman_HtmlFilterText extends \Docman_HtmlFilter
{
    public function __construct($filter)
    {
        parent::__construct($filter);
    }
    // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    #[\Override]
    public function _valueSelectorHtml($formName = 0)
    {
        $html  = '';
        $html .= '<input type="text" name="' . $this->hp->purify($this->filter->md->getLabel()) . '" value="' . $this->hp->purify($this->filter->getValue()) . '" class="text_field" data-test="' . $this->hp->purify($this->filter->md->getLabel()) . '-filter"/>';
        return $html;
    }
}
