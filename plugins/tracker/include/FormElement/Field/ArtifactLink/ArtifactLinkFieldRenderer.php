<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink;

use PFUser;
use TemplateRenderer;
use Tuleap\Tracker\Artifact\Artifact;

final readonly class ArtifactLinkFieldRenderer
{
    public function __construct(
        private TemplateRenderer $template_renderer,
        private EditorWithReverseLinksPresenterBuilder $presenter_builder,
    ) {
    }

    public function render(ArtifactLinkField $field, ?Artifact $artifact, PFUser $user): string
    {
        if ($artifact !== null) {
            $presenter = $this->presenter_builder->buildWithArtifact($field, $artifact, $user);
        } else {
            $presenter = $this->presenter_builder->buildWithoutArtifact($field, $user);
        }
        return $this->template_renderer->renderToString('editor-with-reverse-links', $presenter);
    }
}
