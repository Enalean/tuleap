<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment;

use SimpleXMLElement;
use Tracker_Artifact_Changeset_Comment;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot\Snapshot;
use XML_SimpleXMLCDATAFactory;

class CommentXMLExporter
{
    /**
     * @var XML_SimpleXMLCDATAFactory
     */
    private $simplexml_cdata_factory;

    /**
     * @var CommentXMLValueEnhancer
     */
    private $comment_xml_value_enhancer;

    public function __construct(
        XML_SimpleXMLCDATAFactory $simplexml_cdata_factory,
        CommentXMLValueEnhancer $comment_xml_value_enhancer
    ) {
        $this->simplexml_cdata_factory    = $simplexml_cdata_factory;
        $this->comment_xml_value_enhancer = $comment_xml_value_enhancer;
    }

    public function exportComment(
        Snapshot $snapshot,
        SimpleXMLElement $changeset_node
    ): void {
        $comments_node = $changeset_node->addChild('comments');

        $comment_snapshot = $snapshot->getCommentSnapshot();
        if ($comment_snapshot === null) {
            return;
        }

        $comment_node = $comments_node->addChild('comment');

        $this->simplexml_cdata_factory->insertWithAttributes(
            $comment_node,
            'submitted_by',
            $snapshot->getUser()->getUserName(),
            $format = ['format' => 'username']
        );

        $this->simplexml_cdata_factory->insertWithAttributes(
            $comment_node,
            'submitted_on',
            date('c', $comment_snapshot->getDate()->getTimestamp()),
            $format = ['format' => 'ISO8601']
        );

        $this->simplexml_cdata_factory->insertWithAttributes(
            $comment_node,
            'body',
            $this->comment_xml_value_enhancer->getEnhancedValueWithCommentWriterInformation($comment_snapshot, $snapshot->getUser()),
            ['format' => Tracker_Artifact_Changeset_Comment::HTML_COMMENT]
        );
    }
}
