<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

namespace Tuleap\Widget\Event;

use Tuleap\Event\Dispatchable;
use Tuleap\XML\MappingsRegistry;

class ConfigureAtXMLImport implements Dispatchable
{
    public const NAME = 'configureAtXMLImport';
    /**
     * @var \Widget
     */
    private $widget;
    /**
     * @var \SimpleXMLElement
     */
    private $widget_xml;
    /**
     * @var MappingsRegistry
     */
    private $mappings_registry;
    /**
     * @var bool
     */
    private $isConfigured = false;
    /**
     * @var null|int|false
     */
    private $content_id;
    /**
     * @var \Project
     */
    private $project;

    public function __construct(
        \Widget $widget,
        \SimpleXMLElement $widget_xml,
        MappingsRegistry $mappings_registry,
        \Project $project
    ) {
        $this->widget            = $widget;
        $this->widget_xml        = $widget_xml;
        $this->mappings_registry = $mappings_registry;
        $this->project           = $project;
    }

    public function getWidget()
    {
        return $this->widget;
    }

    public function getXML()
    {
        return $this->widget_xml;
    }

    public function getMappingsRegistry()
    {
        return $this->mappings_registry;
    }

    public function setWidgetIsConfigured()
    {
        $this->isConfigured = true;
    }

    public function isWidgetConfigured()
    {
        return $this->isConfigured;
    }

    public function setContentId($content_id)
    {
        $this->content_id = $content_id;
    }

    public function getContentId()
    {
        return $this->content_id;
    }

    public function getProject(): \Project
    {
        return $this->project;
    }
}
