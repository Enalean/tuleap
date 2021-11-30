<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\Field\CrossReference;

use TemplateRendererFactory;
use Tuleap\Tracker\Artifact\Artifact;
use PFUser;
use Tuleap\Tracker\FormElement\View\Reference\CrossReferenceFieldPresenterBuilder;

class CrossReferenceFieldRenderer
{
    /**
     * @var TemplateRendererFactory
     */
    private $template_renderer;
    /**
     * @var CrossReferenceFieldPresenterBuilder
     */
    private $cross_ref_field_presenter_builder;

    public function __construct(
        TemplateRendererFactory $template_renderer,
        CrossReferenceFieldPresenterBuilder $cross_ref_field_presenter_builder,
    ) {
        $this->template_renderer                 = $template_renderer;
        $this->cross_ref_field_presenter_builder = $cross_ref_field_presenter_builder;
    }

    public function renderCrossReferences(Artifact $artifact, PFUser $user): string
    {
        $can_delete = $user->isSuperUser() || $user->isAdmin((int) $artifact->getTracker()->getGroupId());
        $renderer   = $this->template_renderer->getRenderer(
            __DIR__ . '/../../../../../templates/form-element/reference'
        );

        return $renderer->renderToString(
            'cross_reference_section',
            $this->cross_ref_field_presenter_builder->build($can_delete, $artifact, $user)
        );
    }
}
