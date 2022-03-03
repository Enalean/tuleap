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
use Psr\Log\LoggerInterface;

final class ContainerConvertor extends FormElementConvertor
{
    protected function buildWith(LoggerInterface $logger, IdToNameMapping $id_to_name_mapping): self
    {
        return $this->withFormElements($logger, $id_to_name_mapping);
    }

    private function withFormElements(LoggerInterface $logger, IdToNameMapping $id_to_name_mapping): self
    {
        $form_elements_exprs = [];
        if ($this->xml->formElements) {
            foreach ($this->xml->formElements->formElement as $form_element) {
                $convertor = FormElementConvertorBuilder::buildFromXML($form_element, $this->xml_tracker, $logger);
                if ($convertor) {
                    $form_elements_exprs[] = new Arg($convertor->get($logger, $id_to_name_mapping));
                }
            }

            if ($form_elements_exprs) {
                $this->current_expr = new MethodCall(
                    $this->current_expr,
                    'withFormElements',
                    $form_elements_exprs
                );
            }
        }

        return $this;
    }
}
