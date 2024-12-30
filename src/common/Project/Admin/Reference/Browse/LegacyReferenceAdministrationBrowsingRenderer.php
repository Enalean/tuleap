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
use TemplateRendererFactory;
use Tuleap\Project\Admin\Reference\ReferenceAdministrationWarningsCollectorEvent;

class LegacyReferenceAdministrationBrowsingRenderer
{
    public function __construct(
        private Codendi_HTMLPurifier $purifier,
        private \EventManager $event_manager,
        private \ReferenceManager $reference_manager,
        private TemplateRendererFactory $renderer_factory,
        private ReferencePatternPresenterBuilder $builder,
    ) {
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

        /*
         Show the references that this project is using
        */
        $references = $this->reference_manager->getReferencesByGroupId((int) $project_id); // References are sorted by scope first

        $references_system  = array_filter($references, function ($reference) {
            return $reference->scope === 'S';
        });
        $references_project = array_filter($references, function ($reference) {
            return $reference->scope === 'P';
        });

        $references_system_pattern = [];
        foreach ($references_system as $ref) {
            if ($ref->getId() !== 100) {
                $delete_reference_label      = sprintf(
                    _('*********** WARNING ***********  Do you want to delete the reference pattern? This will remove this reference pattern (%s) from ALL projects of this server. Are you SURE you want to continue ?'),
                    $ref->getKeyword(),
                    CODENDI_PURIFIER_JS_QUOTE
                );
                $references_system_pattern[] = $this->builder->buildProjectReference($ref, $is_template_project, $delete_reference_label);
            }
        }

        $references_project_pattern = [];
        foreach ($references_project as $ref) {
            if ($ref->getId() !== 100) {
                $delete_reference_label       = _('Delete this reference pattern?');
                $references_project_pattern[] = $this->builder->buildProjectReference($ref, $is_template_project, $delete_reference_label);
            }
        }

        $presenter     = new BrowseReferencePresenter($references_system_pattern, $references_project_pattern, $is_template_project);
        $template_path = __DIR__ . '/../../../../../templates/project/admin/references';
        echo $this->renderer_factory->getRenderer($template_path)->renderToString('browse-reference', $presenter);

        $this->displayCustomReferencesWarningsIfAny($references);
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
