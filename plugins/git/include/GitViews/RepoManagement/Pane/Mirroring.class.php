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

namespace Tuleap\Git\GitViews\RepoManagement\Pane;

use GitRepository;
use Codendi_Request;
use GitPresenters_MirroringPresenter;
use TemplateRendererFactory;
use GitPresenters_MirrorPresenter;

class Mirroring extends Pane
{

    /**
     * @var Git_Mirror_Mirror[]
     */
    private $mirrors;

    /**
     * @var Git_Mirror_Mirror[]
     */
    private $repository_mirrors;

    public function __construct(
        GitRepository $repository,
        Codendi_Request $request,
        array $mirrors,
        array $repository_mirrors
    ) {
        parent::__construct($repository, $request);
        $this->mirrors            = $mirrors;
        $this->repository_mirrors = $repository_mirrors;
    }

    /**
     * @see GitViews_RepoManagement_Pane::getIdentifier()
     */
    public function getIdentifier()
    {
        return 'mirroring';
    }

    /**
     * @see GitViews_RepoManagement_Pane::getTitle()
     */
    public function getTitle()
    {
        return ucfirst(dgettext('tuleap-git', 'Mirroring'));
    }

    /**
     * @see GitViews_RepoManagement_Pane::getContent()
     */
    public function getContent()
    {
        $presenter = new GitPresenters_MirroringPresenter($this->repository, $this->getMirrorPresenters());
        $renderer  = TemplateRendererFactory::build()->getRenderer(dirname(GIT_BASE_DIR) . '/templates');

        return $renderer->renderToString('mirroring', $presenter);
    }

    private function getMirrorPresenters()
    {
        $mirror_presenters = array();

        foreach ($this->mirrors as $mirror) {
            $is_used = false;
            if (in_array($mirror, $this->repository_mirrors)) {
                $is_used = true;
            }

            $mirror_presenters[] = new GitPresenters_MirrorPresenter($mirror, $is_used);
        }

        return $mirror_presenters;
    }
}
