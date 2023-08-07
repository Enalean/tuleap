<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Project\Admin\Reference\Browse;

use Codendi_HTMLPurifier;
use Reference;
use Tuleap\Project\Admin\Reference\ReferenceAdministrationWarningsCollectorEvent;

class LegacyReferenceAdministrationBrowsingRenderer
{
    /**
     * @var Codendi_HTMLPurifier
     */
    private $purifier;
    /**
     * @var \EventManager
     */
    private $event_manager;
    /**
     * @var \ReferenceManager
     */
    private $reference_manager;
    /**
     * @var \Tuleap\Reference\NatureCollection
     */
    private $nature_collection;

    public function __construct(
        Codendi_HTMLPurifier $purifier,
        \EventManager $event_manager,
        \ReferenceManager $reference_manager,
    ) {
        $this->purifier          = $purifier;
        $this->event_manager     = $event_manager;
        $this->reference_manager = $reference_manager;
        $this->nature_collection = $this->reference_manager->getAvailableNatures();
    }

    public function render(\Project $project): void
    {
        $project_id          = $project->getID();
        $is_template_project = ((int) $project_id === \Project::DEFAULT_TEMPLATE_PROJECT_ID);

        if ($is_template_project) {
            print '<P><h2>' . _('Editing system reference patterns') . '</B></h2>';
        } else {
            print '<P><h2>'
                . sprintf(
                    _('Editing reference patterns for <B>%s</B>'),
                    $this->purifier->purify($project->getPublicName())
                )
                . '</h2>';
        }
        print '
            <P>
            <H3>' . _('New reference pattern') . '</H3>
            <a href="/project/admin/reference.php?view=creation&group_id=' . urlencode($this->purifier->purify($project_id)) . '">' . _('Create a new reference pattern.') . '</a>
            <p>';

        $this->displayExternalSystemReferences();

        echo '<H3>' . _('Manage system reference patterns') . '</H3>
            <P>
        ';
        /*
         Show the references that this project is using
        */
        $references = $this->reference_manager->getReferencesByGroupId((int) $project_id); // References are sorted by scope first

        $title_arr = [];
        if ($is_template_project) {
            $title_arr[] = _('Reference ID');
        }
        $title_arr[] = _('Keyword');
        $title_arr[] = _('Description');
        $title_arr[] = _('Nature');
        $title_arr[] = _('Status');
        if ($is_template_project) {
            $title_arr[] = _('Scope');
            $title_arr[] = _('Bound to service');
            $title_arr[] = _('Delete?');
        }
        echo html_build_list_table_top($title_arr);
        $current_scope = 'S';

        foreach ($references as $ref) {
            if ($ref->getScope() != $current_scope) {
                //changing from system to project
                echo '</TABLE><H3>' . _('Manage project reference patterns') . '</H3><P>';
                $title_arr_project = [];
                if ($is_template_project) {
                    $title_arr_project[] = _('Reference ID');
                }
                $title_arr_project[] = _('Keyword');
                $title_arr_project[] = _('Description');
                $title_arr_project[] = _('Nature');
                $title_arr_project[] = _('Status');
                if ($is_template_project) {
                    $title_arr_project[] = _('Scope');
                    $title_arr_project[] = _('Bound to service');
                }
                $title_arr_project[] = _('Delete?');
                echo html_build_list_table_top($title_arr_project);
            }
            $current_scope = $ref->getScope();
            $this->displayReferenceRow($ref, $is_template_project);
        }

        $this->displayCustomReferencesWarningsIfAny($references);
    }

    private function displayReferenceRow(\Reference $ref, bool $is_template_project): void
    {
        $purifier = Codendi_HTMLPurifier::instance();

        $can_be_deleted = ($ref->getScope() != "S") || $is_template_project;
        $this->event_manager->processEvent(
            \Event::GET_REFERENCE_ADMIN_CAPABILITIES,
            [
                'reference'      => $ref,
                'can_be_deleted' => &$can_be_deleted,
            ]
        );

        if ($ref->getId() == 100) {
            return; // 'None' reference
        }

        $description = $purifier->purify($ref->getResolvedDescription());

        $available_nature = $this->nature_collection->getNatureFromIdentifier($ref->getNature());
        if ($available_nature) {
            $nature_desc = $purifier->purify($available_nature->label);
        } else {
            $nature_desc = $purifier->purify($ref->getNature());
        }

        echo '<TR>';
        if ($is_template_project) {
            echo '<TD><a href="/project/admin/reference.php?view=edit&group_id=' . $purifier->purify($ref->getGroupId()) . '&reference_id=' . $purifier->purify($ref->getId()) . '" title="' . $description . '">' . $purifier->purify($ref->getId()) . '</TD>';
        }
        echo '<TD><a href="/project/admin/reference.php?view=edit&group_id=' . $purifier->purify($ref->getGroupId()) . '&reference_id=' . $purifier->purify($ref->getId()) . '" title="' . $description . '">' . $purifier->purify($ref->getKeyword()) . '</TD>';
        echo '<TD>' . $description . '</TD>';
        echo '<TD>' . $nature_desc . '</TD>';

        echo '<TD align="center">' . ( $ref->isActive() ? _('Enabled') : _('<i>Disabled</i>') ) . '</TD>';
        if ($is_template_project) {
            $scope = _('System');
            if ($ref->getScope() === 'P') {
                $scope = _('Project');
            }
            echo '<TD align="center">' . $purifier->purify($scope) . '</TD>';
            echo '<TD align="center">' . $purifier->purify($ref->getServiceShortName()) . '</TD>';
        }

        if ($can_be_deleted) {
            $base_url   = '/project/admin/reference.php?group_id=' . urlencode($purifier->purify($ref->getGroupId()));
            $csrf_token = new \CSRFSynchronizerToken($base_url);

            $action_url = $base_url . '&reference_id=' . $purifier->purify($ref->getId()) . '&action=do_delete';

            echo '<TD align="center"><form method="post" action="' . $action_url . '" style="margin: 0;" onsubmit="return confirm(\'';
            if ($ref->getScope() == "S") {
                echo sprintf(
                    _('*********** WARNING ***********  Do you want to delete the reference pattern? This will remove this reference pattern (%s) from ALL projects of this server. Are you SURE you want to continue ?'),
                    $purifier->purify($ref->getKeyword(), CODENDI_PURIFIER_JS_QUOTE)
                );
            } else {
                echo _('Delete this reference pattern?');
            }
            echo '\')"><input type="image" SRC="' . util_get_image_theme("ic/trash.png") . '" HEIGHT="16" WIDTH="16" BORDER="0" ALT="DELETE">';
            echo $csrf_token->fetchHTMLInput();
            echo '</form></TD>';
        } else {
            echo '<td></td>';
        }
        echo '</TR>';
    }

    /**
     * @param Reference[] $references
     */
    private function displayCustomReferencesWarningsIfAny(array $references): void
    {
        $warnings_collector_event = new ReferenceAdministrationWarningsCollectorEvent($references);
        $this->event_manager->dispatch($warnings_collector_event);
        $warning_messages = $warnings_collector_event->getWarningMessages();

        if (empty($warning_messages)) {
            return;
        }

        print '<div class="alert">';
        foreach ($warning_messages as $warning_message) {
            print $warning_message . '<br>';
        }
        print '<div/>';
    }

    private function displayExternalSystemReferences(): void
    {
        $collector = $this->event_manager->dispatch(new ExternalSystemReferencePresentersCollector());

        $presenters = $collector->getExternalSystemReferencePresenters();
        if (! $presenters) {
            return;
        }

        $renderer = \TemplateRendererFactory::build()->getRenderer(
            __DIR__ . '/../../../../../templates/project/admin/references'
        );
        $renderer->renderToPage(
            'external-system-references',
            [
                'external_system_references' => $presenters,
            ]
        );
    }
}
