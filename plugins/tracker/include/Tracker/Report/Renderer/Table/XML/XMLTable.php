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

namespace Tuleap\Tracker\Report\Renderer\Table\XML;

use SimpleXMLElement;
use Tuleap\Tracker\FormElement\XML\XMLFormElementFlattenedCollection;
use Tuleap\Tracker\Report\Renderer\XML\XMLRenderer;
use Tuleap\Tracker\Report\Renderer\Table\Column\XML\XMLTableColumn;

final class XMLTable extends XMLRenderer
{
    private const TYPE = 'table';

    /**
     * @readonly
     */
    private int $chunk_size = 50;
    /**
     * @var XMLTableColumn[]
     * @readonly
     */
    private array $columns = [];

    /**
     * @return static
     * @psalm-mutation-free
     */
    public function withColumns(XMLTableColumn ...$columns): self
    {
        $new          = clone $this;
        $new->columns = array_merge($new->columns, $columns);
        return $new;
    }

    /**
     * @return static
     * @psalm-mutation-free
     */
    public function withChunkSize(int $size): self
    {
        $new             = clone $this;
        $new->chunk_size = $size;
        return $new;
    }

    #[\Override]
    public function export(SimpleXMLElement $renderers, XMLFormElementFlattenedCollection $form_elements): SimpleXMLElement
    {
        $renderer_xml = parent::export($renderers, $form_elements);
        $renderer_xml->addAttribute('type', self::TYPE);
        $renderer_xml->addAttribute('chunksz', (string) $this->chunk_size);

        $renderer_xml->addChild('columns');
        foreach ($this->columns as $column) {
            $column->export($renderer_xml->columns, $form_elements);
        }

        return $renderer_xml;
    }
}
