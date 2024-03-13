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

declare(strict_types=1);

namespace Tuleap\Project\Registration;

use Tuleap\Project\Admin\DescriptionFields\DescriptionFieldLabelBuilder;
use Tuleap\Project\DefaultProjectVisibilityRetriever;
use Tuleap\Project\DescriptionFieldsFactory;
use Tuleap\Project\Registration\Template\CategorisedTemplate;
use Tuleap\Project\Registration\Template\CompanyTemplate;
use Tuleap\Project\Registration\Template\CategorisedTemplatePresenter;
use Tuleap\Project\Registration\Template\ProjectTemplate;
use Tuleap\Project\Registration\Template\TemplateFactory;
use Tuleap\Project\Registration\Template\TemplatePresenter;
use Tuleap\Project\Registration\Template\VerifyProjectCreationFromArchiveIsAllowed;

final readonly class ProjectRegistrationPresenterBuilder
{
    public function __construct(
        private TemplateFactory $template_factory,
        private DefaultProjectVisibilityRetriever $default_project_visibility_retriever,
        private \TroveCatFactory $trove_cat_factory,
        private DescriptionFieldsFactory $fields_factory,
        private VerifyProjectCreationFromArchiveIsAllowed $creation_from_archive_is_allowed,
    ) {
    }

    public function buildPresenter(): ProjectRegistrationPresenter
    {
        $company_templates = array_map(
            static function (CompanyTemplate $project_template) {
                return new TemplatePresenter($project_template);
            },
            $this->template_factory->getCompanyTemplateList()
        );

        $formatted_field = [];
        $fields          = $this->fields_factory->getAllDescriptionFields();
        foreach ($fields as $field) {
            $formatted_field[] = [
                'group_desc_id'    => (string) $field['group_desc_id'],
                'desc_name'        => DescriptionFieldLabelBuilder::getFieldTranslatedName($field['desc_name']),
                'desc_type'        => $field['desc_type'],
                'desc_description' => DescriptionFieldLabelBuilder::getFieldTranslatedDescription($field['desc_description']),
                'desc_required'    => (string) $field['desc_required'],
            ];
        }

        $categorised_external_templates = $this->template_factory->getCategorisedExternalTemplates();
        return new ProjectRegistrationPresenter(
            $this->default_project_visibility_retriever->getDefaultProjectVisibility(),
            $this->trove_cat_factory->getMandatoryParentCategoriesUnderRootOnlyWhenCategoryHasChildren(),
            $formatted_field,
            $company_templates,
            array_map(
                static fn (ProjectTemplate $project_template) => new TemplatePresenter($project_template),
                $this->template_factory->getValidTemplates()
            ),
            array_map(
                static fn(CategorisedTemplate $external_template) => new CategorisedTemplatePresenter($external_template),
                $categorised_external_templates
            ),
            $this->creation_from_archive_is_allowed->canCreateFromCustomArchive(),
        );
    }
}
