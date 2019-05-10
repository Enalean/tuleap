<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Project\Admin;

use BaseLanguage;
use Codendi_HTMLPurifier;

class ProjectVisibilityPresenter
{

    /**
     * @var array
     */
    public $options;

    /**
     * @var bool
     */
    public $platform_allows_restricted;

    /**
     * @var string
     */
    public $restricted_warning_message;

    /**
     * @var string
     */
    public $purified_term_of_service_message;

    /**
     * @var BaseLanguage
     */
    private $language;
    public $can_configure_visibility;
    public $project_visibility_label;
    public $accept_tos_message;
    /**
     * @var int
     */
    public $number_of_restricted_users_in_project;

    public function __construct(
        BaseLanguage $language,
        $platform_allows_restricted,
        $project_visibility,
        int $number_of_restricted_users_in_project,
        ProjectVisibilityOptionsForPresenterGenerator $project_visibility_options_generator
    ) {
        $this->language                         = $language;
        $this->platform_allows_restricted       = (bool) $platform_allows_restricted;
        $this->restricted_warning_message       = $this->language->getText(
            'project_admin_editgroupinfo',
            'restricted_warning'
        );
        $this->general_warning_message          = $this->language->getText(
            'project_admin_editgroupinfo',
            'general_warning'
        );
        $this->purified_term_of_service_message = Codendi_HTMLPurifier::instance()->purify(
            $this->language->getOverridableText('project_admin_editgroupinfo', 'term_of_service'),
            CODENDI_PURIFIER_LIGHT
        );

        $this->project_visibility_label = _('Project visibility');
        $this->accept_tos_message       = _("Please accept term of service");

        $this->options = $project_visibility_options_generator->generateVisibilityOptions(
            $this->platform_allows_restricted,
            $project_visibility
        );
        $this->number_of_restricted_users_in_project = $number_of_restricted_users_in_project;
    }
}
