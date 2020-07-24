<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class ArtifactCommentXMLExporter
{
    public const TEXT = 'text';
    public const HTML = 'html';

    /** @var ArtifactXMLNodeHelper */
    private $node_helper;

    private $all_comments = [];

    public function __construct(ArtifactXMLNodeHelper $node_helper)
    {
        $this->node_helper = $node_helper;
    }

    public function createRootNode(DOMElement $changeset)
    {
        $changeset->appendChild($this->node_helper->createElement('comments'));
    }

    public function appendComment(DOMElement $changeset, array $row)
    {
        $dom_node_list = $changeset->getElementsByTagName('comments');
        $comments_node = $dom_node_list->item(0);

        $this->all_comments[$row['id']] = $comments_node;

        $comments_node->appendChild($this->createCommentNode($row));
    }

    private function createCommentNode(array $row)
    {
        $comment_node = $this->node_helper->createElement('comment');
        $this->node_helper->appendSubmittedBy($comment_node, $row['submitted_by'], $row['is_anonymous']);
        $this->node_helper->appendSubmittedOn($comment_node, $row['date']);
        $comment = Encoding_SupportedXmlCharEncoding::getXMLCompatibleString($row['comment']);
        $body = $this->node_helper->getNodeWithValue('body', $comment);
        $body->setAttribute('format', $this->getFormat($row['format']));
        $comment_node->appendChild($body);
        return $comment_node;
    }

    public function updateComment(array $row)
    {
        $matches = [];
        if (preg_match('/^lbl_(?P<history_id>\d+)_comment$/', $row['field_name'], $matches)) {
            $this->updateCommentNode($matches['history_id'], $row);
            return true;
        }
        return false;
    }

    private function updateCommentNode($reference_id, array $row)
    {
        $this->all_comments[$reference_id]->appendChild($this->createCommentNode($row));
    }

    private function getFormat($format)
    {
        return $format == 0 ? self::TEXT : self::HTML;
    }
}
