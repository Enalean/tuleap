<?php
/**
 * Copyright (c) Enalean, 2014 - 2017. All Rights Reserved.
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

use Tuleap\Layout\IncludeAssets;

require_once('Widget.class.php');

/**
* Widget_Contacts
*
* Allows users to send message to all administrators of a project
*
*/
class Widget_Contacts extends Widget //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{

    public function __construct()
    {
        parent::__construct('projectcontacts');
    }

    public function getTitle()
    {
        return $GLOBALS['Language']->getText('widget_project_contacts', 'title');
    }

    public function getContent()
    {
        $request  = HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $pm       = ProjectManager::instance();
        $project  = $pm->getProject($group_id);

        $token     = new CSRFSynchronizerToken('');
        $presenter = new MassmailFormPresenter(
            $token,
            $GLOBALS['Language']->getText('contact_admins', 'title', array($project->getPublicName())),
            '/include/massmail_to_project_admins.php'
        );
        $template_factory = TemplateRendererFactory::build();
        $renderer         = $template_factory->getRenderer($presenter->getTemplateDir());

        $include_assets = new IncludeAssets(__DIR__ . '/../../www/assets/core', '/assets/core');
        $GLOBALS['HTML']->includeFooterJavascriptFile($include_assets->getFileURL('ckeditor.js'));
        $GLOBALS['HTML']->includeFooterJavascriptFile('/scripts/tuleap/tuleap-ckeditor-toolbar.js');
        $GLOBALS['HTML']->includeFooterJavascriptFile('/scripts/widgets/contact-modal.js');

        $html  = '<a href="javascript:;" ';
        $html .= 'class="massmail-project-member-link project_home_contact_admins" ';
        $html .= 'data-project-id="' . $group_id . '">';
        $html .= '<i class="fa fa-envelope-o massmail-project-member-link-icon"></i>';
        $html .= $GLOBALS['Language']->getText('include_project_home', 'contact_admins');
        $html .= '</a>';

        $html .= $renderer->renderToString('contact-modal', $presenter);

        return $html;
    }

    public function getDescription()
    {
        return $GLOBALS['Language']->getText('widget_description_project_contacts', 'description');
    }
}
