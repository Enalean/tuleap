<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\PullRequest\Notification;

use TemplateRenderer;

final class NotificationTemplatedContent implements NotificationEnhancedContent
{
    /**
     * @var TemplateRenderer
     */
    private $renderer;
    /**
     * @var string
     */
    private $template_name;
    /**
     * @var object
     */
    private $presenter;

    public function __construct(TemplateRenderer $renderer, string $template_name, object $presenter)
    {
        $this->renderer      = $renderer;
        $this->template_name = $template_name;
        $this->presenter     = $presenter;
    }

    public function toString(): string
    {
        return $this->renderer->renderToString(
            $this->template_name,
            $this->presenter
        );
    }
}
