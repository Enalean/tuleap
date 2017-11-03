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

namespace Tuleap\Templating\Mustache;

use Tuleap\Templating\TemplateCache;

class MustacheEngine extends \Mustache_Engine
{
    public function __construct(\Mustache_Loader $loader, TemplateCache $cache, $escape_callback = null)
    {
        $gettext_helper = new GettextHelper();

        parent::__construct(
            array(
                'escape'           => $escape_callback,
                'entity_flags'     => ENT_QUOTES,
                'strict_callables' => true,
                'strict_variables' => true,
                'loader'           => $loader,
                'cache'            => $cache->getPath(),
                'helpers'          => array(
                    'gettext'   => function ($text) use ($gettext_helper) {
                        return $gettext_helper->gettext($text);
                    },
                    'ngettext'  => function ($text, \Mustache_LambdaHelper $helper) use ($gettext_helper) {
                        return $gettext_helper->ngettext($text, $helper);
                    },
                    'dgettext'  => function ($text) use ($gettext_helper) {
                        return $gettext_helper->dgettext($text);
                    },
                    'dngettext' => function ($text, \Mustache_LambdaHelper $helper) use ($gettext_helper) {
                        return $gettext_helper->dngettext($text, $helper);
                    },
                )
            )
        );
    }
}
