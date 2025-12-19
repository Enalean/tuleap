<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Docman\Item\ItemVisitor;
use Tuleap\Docman\Item\OtherDocument;
use Tuleap\Document\Tree\DocumentItemUrlBuilder;

/**
 * @implements ItemVisitor<string>
 */
class Docman_View_Redirect extends Docman_View_View implements ItemVisitor //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
{
    private DocumentItemUrlBuilder $document_item_url_builder;

    public function __construct(Docman_Controller $controller)
    {
        parent::__construct($controller);
        $this->document_item_url_builder = new DocumentItemUrlBuilder(ProjectManager::instance());
    }

    /* protected */ #[\Override]
    public function _content($params) //phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (isset($params['redirect_to'])) {
            $url = $params['redirect_to'];
        } elseif (isset($params['item'])) {
            $event_manager = EventManager::instance();
            $event_manager->processEvent('plugin_docman_event_access', [
                'group_id' => $params['group_id'],
                'item'     => &$params['item'],
                'user'     => &$params['user'],
            ]);
            $url = $params['item']->accept($this, $params);
        } else {
            $url = '/';
        }

        $GLOBALS['Response']->redirect($url);
    }

    #[\Override]
    public function visitFolder(Docman_Folder $item, $params = []): string
    {
        $GLOBALS['Response']->addFeedback(\Feedback::WARN, dgettext('tuleap-docman', 'Your link is not anymore valid: accessing element via the old interface is not supported.'));
        return $this->document_item_url_builder->getRedirectionForFolder($item) . '/';
    }

    #[\Override]
    public function visitWiki(Docman_Wiki $item, $params = []): string
    {
        $project_id = urlencode($item->getGroupId());
        $pagename   = urlencode($item->getPagename());
        return '/wiki/?group_id=' . $project_id . '&pagename=' . $pagename;
    }

    #[\Override]
    public function visitLink(Docman_Link $item, $params = []): string
    {
        $url = null;
        if (isset($params['version_number'])) {
            $version_factory = new Docman_LinkVersionFactory();

            $version = $version_factory->getSpecificVersion($item, $params['version_number']);
            if ($version) {
                $url = $version->getLink();
            }
        }

        if ($url === null) {
            $url = $item->getUrl();
        }

        $valid_localuri = new Valid_LocalURI();
        $valid_ftp      = new Valid_FTPURI();
        if (! $valid_localuri->validate($url) && ! $valid_ftp->validate($url)) {
            return '/';
        }

        $recently_visited_document_dao = new \Tuleap\Document\RecentlyVisited\RecentlyVisitedDocumentDao();
        $recently_visited_document_dao->save(
            (int) $params['user']->getId(),
            (int) $item->getId(),
            \Tuleap\Request\RequestTime::getTimestamp(),
        );

        /**
         * @psalm-taint-escape header
         */
        $header = 'Location: ' . $url;
        header($header);
        exit();
    }

    #[\Override]
    public function visitFile(Docman_File $item, $params = []): string
    {
        $GLOBALS['Response']->addFeedback(\Feedback::WARN, dgettext('tuleap-docman', 'Your link is not anymore valid: accessing element via the old interface is not supported.'));
        return $this->document_item_url_builder->getUrl($item) . '/';
    }

    #[\Override]
    public function visitEmbeddedFile(Docman_EmbeddedFile $item, $params = []): string
    {
        $GLOBALS['Response']->addFeedback(\Feedback::WARN, dgettext('tuleap-docman', 'Your link is not anymore valid: accessing element via the old interface is not supported.'));
        return $this->document_item_url_builder->getRedirectionForEmbeddedFile($item) . '/';
    }

    #[\Override]
    public function visitEmpty(Docman_Empty $item, $params = []): string
    {
        $GLOBALS['Response']->addFeedback(\Feedback::WARN, dgettext('tuleap-docman', 'Your link is not anymore valid: accessing element via the old interface is not supported.'));
        return $this->document_item_url_builder->getUrl($item) . '/';
    }

    #[\Override]
    public function visitItem(Docman_Item $item, array $params = []): string
    {
        $GLOBALS['Response']->addFeedback(\Feedback::WARN, dgettext('tuleap-docman', 'Your link is not anymore valid: accessing element via the old interface is not supported.'));
        return $this->document_item_url_builder->getUrl($item) . '/';
    }

    #[\Override]
    public function visitOtherDocument(OtherDocument $item, array $params = []): string
    {
        return EventManager::instance()
            ->dispatch(new \Tuleap\Docman\Item\OtherDocumentHrefEvent($item))
            ->getHref();
    }
}
