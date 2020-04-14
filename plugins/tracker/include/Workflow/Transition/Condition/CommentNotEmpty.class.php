<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

use Tuleap\Tracker\Workflow\Transition\Condition\Visitor;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Workflow_Transition_Condition_CommentNotEmpty extends Workflow_Transition_Condition
{
    /** @var bool */
    private $is_comment_required;

    /** @var string */
    public $identifier = 'commentnotempty';

    /** @var Workflow_Transition_Condition_CommentNotEmpty_Dao */
    private $dao;

    public function __construct(
        Transition $transition,
        Workflow_Transition_Condition_CommentNotEmpty_Dao $dao,
        $is_comment_required
    ) {
        parent::__construct($transition);
        $this->dao                 = $dao;
        $this->is_comment_required = $is_comment_required;
    }

    /**
     * @return bool
     */
    public function isCommentRequired()
    {
        return $this->is_comment_required;
    }

    /**
     * @see Workflow_Transition_Condition::exportToXml()
     */
    public function exportToXml(SimpleXMLElement $root, $xmlMapping)
    {
        if (! $this->is_comment_required) {
            return;
        }

        $child = $root->addChild('condition');
        $child->addAttribute('type', $this->identifier);
        $child->addAttribute('is_comment_required', "1");
    }

    /**
     * @see Workflow_Transition_Condition::saveObject()
     */
    public function saveObject()
    {
        $this->dao->create($this->getTransition()->getId(), $this->is_comment_required);
    }

    /**
     *
     * @return bool
     */
    public function validate($fields_data, Tracker_Artifact $artifact, $comment_body)
    {
        if (! $this->is_comment_required) {
            return true;
        }

        if (trim($comment_body) === '') {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('workflow_admin', 'label_define_transition_required_comment')
            );
            return false;
        }

        return true;
    }

    public function accept(Visitor $visitor)
    {
        $visitor->visitCommentNotEmpty($this);
    }
}
