<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\Templating\Mustache;

use Codendi_HTMLPurifier;
use PFUser;
use Tuleap\Language\CustomizableContentLoader;
use Tuleap\Templating\TemplateCache;

/**
 * Renders .mustache from site content with support of local overloading and language fallbacks
 */
class SiteContentRenderer
{
    /**
     * Render mustache file according to user language preferences and available customization
     *
     * @param string $template_name
     * @param mixed  $presenter
     * @return string
     */
    public function render(PFUser $user, $template_name, $presenter)
    {
        try {
            $template_cache    = new TemplateCache();
            $mustache_renderer = new \Mustache_Engine(array('cache' => $template_cache->getPath()));
            $content_loader    = new CustomizableContentLoader();
            return $mustache_renderer->render(
                $content_loader->getContent($user, $template_name),
                $presenter
            );
        } catch (\Mustache_Exception_InvalidArgumentException $exception) {
        }
        return '';
    }

    /**
     * Render template with markdown syntax
     *
     * @param string $template_name
     * @param mixed  $presenter
     * @return string
     */
    public function renderMarkdown(PFUser $user, $template_name, $presenter)
    {
        $markdown_renderer = \Tuleap\Markdown\CommonMarkInterpreter::build(Codendi_HTMLPurifier::instance());
        return $markdown_renderer->getInterpretedContent(
            $this->render(
                $user,
                $template_name,
                $presenter
            )
        );
    }
}
