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

use EventManager;
use Tuleap\Glyph\GlyphFinder;
use Tuleap\Templating\TemplateCacheInterface;

class MustacheEngine extends \Mustache_Engine
{
    public function __construct(\Mustache_Loader $loader, TemplateCacheInterface $cache, $escape_callback = null)
    {
        $gettext_helper = new GettextHelper(new GettextSectionContentTransformer());
        $glyph_helper   = new GlyphHelper(new GlyphFinder(EventManager::instance()));

        parent::__construct(
            array(
                'escape'           => $escape_callback,
                'entity_flags'     => ENT_QUOTES,
                'strict_callables' => true,
                'strict_variables' => true,
                'loader'           => $loader,
                'cache'            => $cache->getPath(),
                'helpers'          => array(
                    GettextHelper::GETTEXT   => function ($text) use ($gettext_helper) {
                        return $gettext_helper->gettext($text);
                    },
                    GettextHelper::NGETTEXT  => function ($text, \Mustache_LambdaHelper $helper) use ($gettext_helper) {
                        return $gettext_helper->ngettext($text, $helper);
                    },
                    GettextHelper::DGETTEXT  => function ($text) use ($gettext_helper) {
                        return $gettext_helper->dgettext($text);
                    },
                    GettextHelper::DNGETTEXT => function ($text, \Mustache_LambdaHelper $helper) use ($gettext_helper) {
                        return $gettext_helper->dngettext($text, $helper);
                    },
                    GlyphHelper::GLYPH => function ($text) use ($glyph_helper) {
                        return $glyph_helper->glyph($text);
                    },
                )
            )
        );
    }
}
