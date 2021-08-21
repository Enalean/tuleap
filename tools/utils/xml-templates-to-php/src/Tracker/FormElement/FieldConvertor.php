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

class FieldConvertor extends FormElementConvertor
{
    protected function buildWith(LoggerInterface $logger, IdToNameMapping $id_to_name_mapping): self
    {
        parent::buildWith($logger, $id_to_name_mapping);

        return $this->withPermissions();
    }

    private function withPermissions(): self
    {
        $permissions       = [];
        $field_id          = (string) $this->xml['ID'];
        $field_permissions = $this->xml_tracker->xpath("//permission[@scope='field'][@REF='$field_id']");
        $mapping           = [
            'PLUGIN_TRACKER_FIELD_READ'   => \Tuleap\Tracker\FormElement\Field\XML\ReadPermission::class,
            'PLUGIN_TRACKER_FIELD_UPDATE' => \Tuleap\Tracker\FormElement\Field\XML\UpdatePermission::class,
            'PLUGIN_TRACKER_FIELD_SUBMIT' => \Tuleap\Tracker\FormElement\Field\XML\SubmitPermission::class,
        ];
        foreach ($field_permissions as $field_permission) {
            $type = (string) $field_permission['type'];
            if (isset($mapping[$type])) {
                $permissions[] = new Arg(
                    new New_(
                        new Name('\\' . $mapping[$type]),
                        [new Arg(new String_((string) $field_permission['ugroup']))],
                    )
                );
            }
        }

        if ($permissions) {
            $this->current_expr = new MethodCall(
                $this->current_expr,
                'withPermissions',
                $permissions
            );
        }

        return $this;
    }
}
