<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Project\MappingRegistry;
use Tuleap\Tracker\Widget\WidgetRendererDao;

/**
 * Widget_MyTrackerRenderer
 *
 * Personal tracker renderer
 */
class Tracker_Widget_ProjectRenderer extends Tracker_Widget_Renderer //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
{
    public const ID = 'plugin_tracker_projectrenderer';
    private Tracker_Report_RendererFactory $renderer_factory;

    public function __construct(?Tracker_Report_RendererFactory $renderer_factory = null)
    {
        parent::__construct(
            self::ID,
            HTTPRequest::instance()->get('group_id'),
            \Tuleap\Dashboard\Project\ProjectDashboardController::LEGACY_DASHBOARD_TYPE
        );
        if ($renderer_factory === null) {
            $this->renderer_factory = Tracker_Report_RendererFactory::instance();
        } else {
            $this->renderer_factory = $renderer_factory;
        }
    }

    #[\Override]
    public function cloneContent(
        Project $template_project,
        Project $new_project,
        $id,
        $owner_id,
        $owner_type,
        MappingRegistry $mapping_registry,
    ): int {
        $dao = new WidgetRendererDao();

        if (! $mapping_registry->hasCustomMapping(Tracker_Report_RendererFactory::MAPPING_KEY)) {
            return $dao->cloneContent(
                (int) $this->owner_id,
                $this->owner_type,
                (int) $owner_id,
                (string) $owner_type
            );
        }

        $transaction_executor = new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection());

        return $transaction_executor->execute(
            function () use ($id, $dao, $owner_id, $owner_type, $mapping_registry): int {
                $data = $dao->searchContent($this->owner_id, $this->owner_type, (int) $id);
                if (! $data) {
                    return $dao->cloneContent(
                        $this->owner_id,
                        $this->owner_type,
                        (int) $owner_id,
                        (string) $owner_type
                    );
                }

                $item_mapping = $mapping_registry->getCustomMapping(Tracker_Report_RendererFactory::MAPPING_KEY);
                if (! isset($item_mapping[$data['renderer_id']])) {
                    return $dao->insertContent(
                        (int) $owner_id,
                        (string) $owner_type,
                        $data['title'],
                        $data['renderer_id'],
                    );
                }

                return $dao->insertContent(
                    (int) $owner_id,
                    (string) $owner_type,
                    $data['title'],
                    $item_mapping[$data['renderer_id']],
                );
            }
        );
    }

    #[\Override]
    public function isAjax()
    {
        return false;
    }

    #[\Override]
    public function exportAsXML(): ?\SimpleXMLElement
    {
        $renderer = $this->renderer_factory->getReportRendererById($this->renderer_id, null, false);
        if ($renderer === null) {
            return null;
        }

        $report = $renderer->getReport();
        if (! $report->isPublic()) {
            return null;
        }

        $tracker = $report->getTracker();
        if ($tracker->isDeleted()) {
            return null;
        }

        $current_project_id = (int) $this->owner_id;
        if ($current_project_id !== (int) $tracker->getProject()->getId()) {
            return null;
        }


        $widget = new \SimpleXMLElement('<widget />');
        $widget->addAttribute('name', $this->id);

        $preference = $widget->addChild('preference');
        $preference->addAttribute('name', 'renderer');

        $cdata_factory = new \XML_SimpleXMLCDATAFactory();
        $cdata_factory->insertWithAttributes(
            $preference,
            'value',
            (string) $this->renderer_title,
            ['name' => 'title']
        );

        $reference = $preference->addChild('reference');
        $reference->addAttribute('name', 'id');
        $reference->addAttribute('REF', \Tracker_Report_Renderer::XML_ID_PREFIX . $this->renderer_id);

        return $widget;
    }
}
