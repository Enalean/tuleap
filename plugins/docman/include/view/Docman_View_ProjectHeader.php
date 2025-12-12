<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

/* abstract */ class Docman_View_ProjectHeader extends Docman_View_Header //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
{
    /* protected */ #[Override]
    public function _scripts($params) //phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $project   = ProjectManager::instance()->getProject((int) $params['group_id']);
        $purifier  = Codendi_HTMLPurifier::instance();
        $csp_nonce = \Tuleap\ContentSecurityPolicy\CSPNonce::build();
    }

    /* protected */ public function _getJSDocmanParameters($params) //phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return [];
    }
}
