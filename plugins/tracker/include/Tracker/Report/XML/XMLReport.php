<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Report\XML;

use SimpleXMLElement;
use Tuleap\Tracker\FormElement\XML\XMLFormElementFlattenedCollection;
use Tuleap\Tracker\Report\Renderer\XML\XMLRenderer;
use XML_SimpleXMLCDATAFactory;

final class XMLReport
{
    /**
     * @readonly
     */
    private bool $is_default = false;
    /**
     * @readonly
     */
    private bool $is_in_expert_mode = false;
    /**
     * @readonly
     */
    private string $expert_query = '';
    /**
     * @readonly
     */
    private string $description = '';
    /**
     * @var XMLReportCriterion[]
     * @readonly
     */
    private array $criteria = [];

    /**
     * @var XMLRenderer[]
     * @readonly
     */
    private array $renderers = [];

    public function __construct(
        /**
         * @readonly
         */
        private string $name,
    ) {
    }

    /**
     * @psalm-mutation-free
     */
    public function withIsDefault(bool $is_default): self
    {
        $new             = clone $this;
        $new->is_default = $is_default;
        return $new;
    }

    /**
     * @psalm-mutation-free
     */
    public function withExpertMode(): self
    {
        $new                    = clone $this;
        $new->is_in_expert_mode = true;
        return $new;
    }

    /**
     * @psalm-mutation-free
     */
    public function withExpertQuery(string $expert_query): self
    {
        $new               = clone $this;
        $new->expert_query = $expert_query;
        return $new;
    }

    /**
     * @psalm-mutation-free
     */
    public function withDescription(string $description): self
    {
        $new              = clone $this;
        $new->description = $description;
        return $new;
    }

    /**
     * @psalm-mutation-free
     */
    public function withCriteria(XMLReportCriterion ...$criterion): self
    {
        $new           = clone $this;
        $new->criteria = array_merge($new->criteria, $criterion);
        return $new;
    }

    /**
     * @psalm-mutation-free
     */
    public function withRenderers(XMLRenderer ...$renderers): self
    {
        $new            = clone $this;
        $new->renderers = array_merge($new->renderers, $renderers);
        return $new;
    }

    public function export(SimpleXMLElement $tracker, XMLFormElementFlattenedCollection $form_elements): SimpleXMLElement
    {
        $xml_report = $tracker->addChild('report');

        if ($this->is_default) {
            $xml_report->addAttribute('is_default', '1');
        }

        if ($this->is_in_expert_mode) {
            $xml_report->addAttribute('is_in_expert_mode', '1');
        }

        if ($this->expert_query) {
            $xml_report->addAttribute('expert_query', $this->expert_query);
        }

        $cdata = new XML_SimpleXMLCDATAFactory();
        $cdata->insert($xml_report, 'name', $this->name);
        if ($this->description) {
            $cdata->insert($xml_report, 'description', $this->description);
        }

        $criterias = $xml_report->addChild('criterias');
        foreach ($this->criteria as $criterion) {
            $criterion->export($criterias, $form_elements);
        }

        $renderers = $xml_report->addChild('renderers');
        foreach ($this->renderers as $renderer) {
            $renderer->export($renderers, $form_elements);
        }

        return $xml_report;
    }
}
