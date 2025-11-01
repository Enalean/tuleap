<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

namespace Tuleap\Git\GitViews\RepoManagement\Pane;

class Delete extends Pane
{
    /**
     * @see GitViews_RepoManagement_Pane::getIdentifier()
     */
    #[\Override]
    public function getIdentifier()
    {
        return 'delete';
    }

    /**
     * @see GitViews_RepoManagement_Pane::getTitle()
     */
    #[\Override]
    public function getTitle()
    {
        return ucfirst($GLOBALS['Language']->getText('global', 'delete'));
    }

    /**
     * @see GitViews_RepoManagement_Pane::getContent()
     */
    #[\Override]
    public function getContent()
    {
        $html = '<section class="tlp-pane">
            <div class="tlp-pane-container">
                <div class="tlp-pane-header">
                    <h1 class="tlp-pane-title">' . dgettext('tuleap-git', 'Delete this repository') . '</h1>
                </div>
                <form class="tlp-pane-section" method="POST" action="/plugins/git/?group_id=' . $this->repository->getProjectId() . '">
                    <input type="hidden" id="repo_id" name="repo_id" value="' . $this->repository->getId() . '" />';

        if (! $this->repository->isMigratedToGerrit()) {
            if ($this->request->get('confirm_deletion')) {
                $html .= $this->fetchConfirmDeletionButton();
            } else {
                $html .= $this->fetchDeleteButton();
            }
        } else {
            $html .= $this->fetchGerritMigtatedInfo();
        }

        $html .= '</form>
            </div>
        </section>';

        return $html;
    }

    private function fetchDeleteButton()
    {
        $html     = '';
        $html    .= '<input type="hidden" id="action" name="action" value="repo_management" />';
        $html    .= '<input type="hidden" name="pane" value="' . $this->getIdentifier() . '" />';
        $disabled = '';
        if (! $this->repository->canBeDeleted()) {
            $html    .= '<div class="tlp-alert-danger">' . 'You cannot delete' . '</div>';
            $disabled = 'readonly="readonly" disabled="disabled"';
        }
        $html .= '<input type="submit" class="tlp-button-danger" name="confirm_deletion" value="' . dgettext('tuleap-git', 'Delete this repository') . '" ' . $disabled . 'data-test="confirm-repository-deletion-button"/>';
        return $html;
    }

    private function fetchConfirmDeletionButton()
    {
        $html    = '';
        $html   .= '<div class="tlp-alert-warning">';
        $html   .= '<h4>' . $GLOBALS['Language']->getText('global', 'warning') . '</h4>';
        $html   .= '<p>' . sprintf(dgettext('tuleap-git', 'You are about to permanently delete the repository <strong>%1$s</strong>. This operation <strong>cannot</strong> be undone. Do you confirm the deletion?'), $this->repository->getFullName()) . '</p>';
        $html   .= '<p>';
        $html   .= '<input type="hidden" id="action" name="action" value="del" />';
        $html   .= $this->csrf_token()->fetchHTMLInput();
        $html   .= '<input type="submit" class="tlp-button-danger" id="submit" name="submit" value="' . dgettext('tuleap-git', 'Yes') . '"data-test="deletion-confirmation-button"/> ';
        $onclick = 'onclick="window.location=\'/plugins/git/?' . http_build_query([
            'action'   => 'repo_management',
            'pane'     => $this->getIdentifier(),
            'group_id' => $this->repository->getProjectId(),
            'repo_id'  => $this->repository->getId(),
        ]) . '\'"';
        $html   .= '<input type="button" class="tlp-button-danger tlp-button-outline" value="' . dgettext('tuleap-git', 'No') . '" ' . $onclick . '/>';
        $html   .= '</p>';
        $html   .= '</div>';
        return $html;
    }

    private function fetchGerritMigtatedInfo()
    {
        $html = '<div class="tlp-alert-info">' . dgettext('tuleap-git', 'The repository cannot be deleted until it is disconnected from Gerrit') . '</div>';

        return $html;
    }
}
