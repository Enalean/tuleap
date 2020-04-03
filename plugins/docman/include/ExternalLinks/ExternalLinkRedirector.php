<?php
/**
 * Copyright (c) Enalean, 2019-present. All Rights Reserved.
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

namespace Tuleap\Docman\ExternalLinks;

use Tuleap\Event\Dispatchable;

class ExternalLinkRedirector implements Dispatchable
{
    public const NAME = 'externalLinkRedirector';
    /**
     * @var null | int
     */
    private $document_id;

    /**
     * @var \PFUser
     */
    private $user;
    /**
     * @var \Project
     */
    private $project;

    /**
     * @var \HTTPRequest
     */
    private $request;

    /**
     * @var bool
     */
    private $should_redirect_user = false;
    /**
     * @var int
     */
    private $folder_id;
    /**
     * @var int
     */
    private $root_folder_id;

    public function __construct(\PFUser $user, \HTTPRequest $request, int $folder_id, int $root_folder_id)
    {
        $this->user           = $user;
        $this->project        = $request->getProject();
        $this->request        = $request;
        $this->folder_id      = $folder_id;
        $this->root_folder_id = $root_folder_id;
    }

    public function getUrlRedirection(): string
    {
        if ($this->document_id && $this->root_folder_id !== $this->document_id) {
            return "/plugins/document/" . urlencode($this->project->getUnixNameLowerCase()) .
                "/preview/" . urlencode((string) $this->document_id);
        }

        if ($this->folder_id === 0) {
            return "/plugins/document/" . urlencode($this->project->getUnixNameLowerCase()) . "/";
        }

        return "/plugins/document/" . urlencode($this->project->getUnixNameLowerCase()) . "/" . $this->folder_id;
    }

    public function shouldRedirectUserOnNewUI(): bool
    {
        return $this->should_redirect_user;
    }

    public function checkAndStoreIfUserHasToBeenRedirected(bool $should_use_document_url): void
    {
        if ($this->user->isAnonymous()) {
            return;
        }

        $is_request_for_legacy_docman = $this->request->exist("action");
        if ($is_request_for_legacy_docman) {
            $this->should_redirect_user = false;

            return;
        }

        $this->checkAndStoreDocumentIdIfUserCanAccessToLegacyLinkToDocumentUrl();
        $this->useUserPreferencesWhenUserTryToAccessToDocument($should_use_document_url);
    }

    public function getProject(): \Project
    {
        return $this->project;
    }

    private function checkAndStoreDocumentIdIfUserCanAccessToLegacyLinkToDocumentUrl(): void
    {
        if ($this->request->exist("group_id") && $this->request->exist("id")) {
            $this->document_id = (int) $this->request->get("id");
            $this->should_redirect_user = true;
        }
    }

    /**
     * In notifications we always write url with new links,
     * If user try to access to a document from an old mail, he should be redirected to the preview
     */
    private function useUserPreferencesWhenUserTryToAccessToDocument(bool $should_use_document_url): void
    {
        if (! $this->document_id) {
            $this->should_redirect_user = $should_use_document_url;
        }
    }
}
