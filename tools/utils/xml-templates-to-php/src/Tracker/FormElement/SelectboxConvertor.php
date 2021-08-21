<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tools\Xml2Php\Tracker\FormElement;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use Psr\Log\LoggerInterface;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStatic\XML\XMLBindStaticValue;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindUsers\XML\XMLBindUsersValue;

final class SelectboxConvertor extends FieldConvertor
{
    protected function buildWith(LoggerInterface $logger, IdToNameMapping $id_to_name_mapping): self
    {
        parent::buildWith($logger, $id_to_name_mapping);

        return $this->withBind($logger);
    }

    private function withBind(LoggerInterface $logger): self
    {
        $type = (string) $this->xml->bind['type'];

        if ($type === 'static') {
            return $this->withBindStatic();
        } elseif ($type === 'users') {
            return $this->withBindUsers($logger);
        }

        return $this;
    }

    private function withBindStatic(): self
    {
        if ((string) $this->xml->bind['is_rank_alpha'] === '1') {
            $this->current_expr = new MethodCall(
                $this->current_expr,
                'withAlphanumericRank'
            );
        }

        $values = [];
        foreach ($this->xml->bind->items->item as $item) {
            $value = new New_(
                new Name('\\' . XMLBindStaticValue::class),
                [
                    new Arg(new String_((string) $item['ID'])),
                    new Arg(new String_((string) $item['label'])),
                ]
            );

            if ((string) $item->description) {
                $value = new MethodCall(
                    $value,
                    'withDescription',
                    [new Arg(new String_((string) $item->description))]
                );
            }

            if ($this->xml->bind->xpath('default_values/value[@REF=\'' . $item['ID'] . '\']')) {
                $value = new MethodCall($value, 'withIsDefault');
            }

            if ($this->xml->bind->decorators) {
                foreach ($this->xml->bind->decorators->decorator as $decorator) {
                    if ((string) $decorator['REF'] === (string) $item['ID'] && (string) $decorator['tlp_color_name']) {
                        $value = new MethodCall(
                            $value,
                            'withDecorator',
                            [new Arg(new String_((string) $decorator['tlp_color_name']))]
                        );
                    }
                }
            }
            $values[] = new Arg($value);
        }

        if ($values) {
            $this->current_expr = new MethodCall(
                $this->current_expr,
                'withStaticValues',
                $values
            );
        }

        return $this;
    }

    private function withBindUsers(LoggerInterface $logger): self
    {
        $values = [];
        foreach ($this->xml->bind->items->item as $item) {
            $values[] = new Arg(
                new New_(
                    new Name('\\' . XMLBindUsersValue::class),
                    [
                        new Arg(new String_((string) $item['label'])),
                    ]
                )
            );
        }

        if ($values) {
            $this->current_expr = new MethodCall(
                $this->current_expr,
                'withUsersValues',
                $values
            );
        }

        if ($this->xml->bind->default_values) {
            $logger->error('Default values for users lists are not supported yet.');
        }

        return $this;
    }
}
