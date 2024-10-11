<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\PdfTemplate\Admin;

use Tuleap\CSRFSynchronizerTokenPresenter;
use Tuleap\Export\Pdf\Template\PdfTemplate;
use Tuleap\Request\CSRFSynchronizerTokenInterface;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;

/**
 * @psalm-immutable
 */
final readonly class IndexPdfTemplatePresenter
{
    public bool $has_templates;
    public CSRFSynchronizerTokenPresenter $csrf;
    /**
     * @var list<PdfTemplatePresenter>
     */
    public array $templates;

    /**
     * @param list<PdfTemplate> $templates
     */
    public function __construct(
        public Navigation $navigation,
        public string $create_url,
        public string $delete_url,
        array $templates,
        CSRFSynchronizerTokenInterface $token,
        \PFUser $user,
        ProvideUserAvatarUrl $provide_user_avatar_url,
    ) {
        $this->has_templates = count($templates) > 0;
        $this->csrf          = CSRFSynchronizerTokenPresenter::fromToken($token);
        $presenters          = [];
        foreach ($templates as $template) {
            $presenters[] = PdfTemplatePresenter::fromPdfTemplate($template, $user, $provide_user_avatar_url);
        }
        $this->templates = $presenters;
    }
}
