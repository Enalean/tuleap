<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

declare(strict_types = 1);

namespace Tuleap\Project\Registration;

use Tuleap\Project\DefaultProjectVisibilityRetriever;
use Tuleap\Project\DescriptionFieldsFactory;
use Tuleap\Project\Registration\Template\ProjectTemplate;
use Tuleap\Project\Registration\Template\TemplateFactory;
use Tuleap\Project\Registration\Template\TemplatePresenter;

class ProjectRegistrationPresenterBuilder
{
    /**
     * @var TemplateFactory
     */
    private $template_factory;
    /**
     * @var DefaultProjectVisibilityRetriever
     */
    private $default_project_visibility_retriever;
    /**
     * @var \TroveCatFactory
     */
    private $trove_cat_factory;
    /**
     * @var DescriptionFieldsFactory
     */
    private $fields_factory;

    public function __construct(
        TemplateFactory $template_factory,
        DefaultProjectVisibilityRetriever $default_project_visibility_retriever,
        \TroveCatFactory $trove_cat_factory,
        DescriptionFieldsFactory $fields_factory
    ) {
        $this->template_factory                     = $template_factory;
        $this->default_project_visibility_retriever = $default_project_visibility_retriever;
        $this->trove_cat_factory                    = $trove_cat_factory;
        $this->fields_factory                       = $fields_factory;
    }

    public function buildPresenter(): ProjectRegistrationPresenter
    {

        return new ProjectRegistrationPresenter(
            $this->default_project_visibility_retriever->getDefaultProjectVisibility(),
            $this->trove_cat_factory->getMandatoryParentCategoriesUnderRootOnlyWhenCategoryHasChildren(),
            $this->fields_factory->getAllDescriptionFields(),
            ...array_map(
                static function (ProjectTemplate $project_template) {
                    return new TemplatePresenter($project_template);
                },
                $this->template_factory->getValidTemplates()
            )
        );
    }
}
