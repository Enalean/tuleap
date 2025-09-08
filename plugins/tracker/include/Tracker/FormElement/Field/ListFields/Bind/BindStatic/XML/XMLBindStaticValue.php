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

namespace Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStatic\XML;

use Tuleap\Tracker\FormElement\Field\ListFields\XML\XMLListField;
use Tuleap\Tracker\FormElement\FieldNameFormatter;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\XML\XMLBindValue;
use Tuleap\Tracker\FormElement\Field\ListFields\XML\XMLDecorator;
use Tuleap\Tracker\XML\IDGenerator;

final class XMLBindStaticValue implements XMLBindValue
{
    /**
     * @readonly
     */
    public string $id;
    /**
     * @readonly
     */
    public string $id_for_field_change;
    /**
     * @readonly
     */
    public string $label;
    /**
     * @readonly
     */
    private ?XMLDecorator $decorator = null;
    /**
     * @readonly
     */
    private string $description = '';
    /**
     * @readonly
     */
    public bool $is_default = false;

    public function __construct(string|IDGenerator $id, string $label)
    {
        if ($id instanceof IDGenerator) {
            $next_id                   = $id->getNextId();
            $this->id_for_field_change = (string) $next_id;
            $this->id                  = sprintf('V%d', $next_id);
        } else {
            $this->id_for_field_change = substr($id, 1);
            $this->id                  = $id;
        }
        $this->label = $label;
    }

    public static function fromLabel(XMLListField $field, string $label): self
    {
        return new self(sprintf('V%s_%s', $field->id, FieldNameFormatter::getFormattedName($label)), $label);
    }

    /**
     * @psalm-mutation-free
     */
    public function withIsDefault(): self
    {
        $new             = clone $this;
        $new->is_default = true;

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
    public function withDecorator(string $tlp_color_name): self
    {
        $new            = clone $this;
        $new->decorator = new XMLDecorator($this->id, $tlp_color_name);

        return $new;
    }

    #[\Override]
    public function export(\SimpleXMLElement $bind, \SimpleXMLElement $values): void
    {
        $item = $values->addChild('item');
        $item->addAttribute('ID', $this->id);
        $item->addAttribute('label', $this->label);

        if ($this->description) {
            (new \XML_SimpleXMLCDATAFactory())->insert($item, 'description', $this->description);
        }

        $this->exportDecorator($bind);
        $this->exportDefaultValue($bind);
    }

    private function exportDecorator(\SimpleXMLElement $bind): void
    {
        if (! $this->decorator) {
            return;
        }

        $decorators = $bind->decorators ?: $bind->addChild('decorators');
        $this->decorator->export($decorators);
    }

    private function exportDefaultValue(\SimpleXMLElement $bind): void
    {
        if (! $this->is_default) {
            return;
        }
        $default_values = $bind->default_values ?: $bind->addChild('default_values');
        $default_values->addChild('value')->addAttribute('REF', $this->id);
    }
}
