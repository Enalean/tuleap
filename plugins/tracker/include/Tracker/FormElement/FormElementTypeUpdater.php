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

class FormElementTypeUpdater
{
    /**
     * @var DBTransactionExecutor
     */
    private $db_transaction_executor;

    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;

    public function __construct(
        DBTransactionExecutor $db_transaction_executor,
        Tracker_FormElementFactory $form_element_factory,
    ) {
        $this->db_transaction_executor = $db_transaction_executor;
        $this->form_element_factory    = $form_element_factory;
    }

    /**
     * @throws FormElementTypeUpdateErrorException
     */
    public function updateFormElementType(\Tracker_FormElement $form_element, string $type): void
    {
        $this->db_transaction_executor->execute(
            function () use ($form_element, $type) {
                $success = $this->form_element_factory->changeFormElementType($form_element, $type);
                if ($success === false) {
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
                    $success = $this->form_element_factory->changeFormElementType($target_field, $type);
                    if ($success === false) {
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
}
