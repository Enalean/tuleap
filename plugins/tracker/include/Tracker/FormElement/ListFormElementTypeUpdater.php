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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement;

use Feedback;
use Tracker_FormElementFactory;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Tracker\FormElement\Field\FieldDao;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindDefaultValueDao;

class ListFormElementTypeUpdater
{
    public function __construct(
        private DBTransactionExecutor $db_transaction_executor,
        private Tracker_FormElementFactory $form_element_factory,
        private FieldDao $field_dao,
        private BindDefaultValueDao $bind_default_value_dao,
    ) {
    }

    /**
     * @throws FormElementTypeUpdateErrorException
     */
    public function updateFormElementType(Field\ListField $form_element, string $type): void
    {
        $this->db_transaction_executor->execute(
            function () use ($form_element, $type) {
                if ($this->changeFormElementType($form_element, $type) === false) {
                    throw new FormElementTypeUpdateErrorException(
                        dgettext('tuleap-tracker', 'Field type could not be changed')
                    );
                }

                $target_fields = $form_element->getSharedTargets();
                if (empty($target_fields)) {
                    $GLOBALS['Response']->addFeedback(
                        Feedback::INFO,
                        dgettext('tuleap-tracker', 'Field type successfully changed')
                    );
                    return;
                }

                foreach ($target_fields as $target_field) {
                    assert($target_field instanceof Field\ListField);

                    if ($this->changeFormElementType($target_field, $type) === false) {
                        throw new FormElementTypeUpdateErrorException(
                            dgettext('tuleap-tracker', 'Field type could not be changed for a target field')
                        );
                    }
                }

                $GLOBALS['Response']->addFeedback(
                    Feedback::INFO,
                    dgettext('tuleap-tracker', 'Field type successfully changed for the field and all its target fields')
                );
            }
        );
    }

    private function changeFormElementType(Field\ListField $form_element, string $type): bool
    {
        if (! $form_element->changeType($type) || ! $this->field_dao->setType($form_element, $type)) {
            return false;
        }

        $this->form_element_factory->clearElementFromCache($form_element); //todo: clear other caches?

        $new_form_element = $this->form_element_factory->getFormElementById($form_element->getId());
        if (! $new_form_element instanceof Field\ListField) {
            return false;
        }

        $new_form_element->storeProperties($new_form_element->getFlattenPropertiesValues());
        $this->clearDefaultValuesIfNeeded($form_element, $new_form_element);

        return true;
    }

    private function clearDefaultValuesIfNeeded(Field\ListField $old_form_element, Field\ListField $new_form_element): void
    {
        // Clear default values when type changes from a multiple list to a simple list
        if ($old_form_element->isMultiple() && ! $new_form_element->isMultiple()) {
            $this->bind_default_value_dao->save($new_form_element->getId(), []);
        }
    }
}
