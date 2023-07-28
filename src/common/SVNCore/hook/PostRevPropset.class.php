<?php
/**
 * Copyright Enalean (c) 2013 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

use Tuleap\Reference\CrossReference;

/**
 * I'm responsible of handling what happens in post-revprop-change subversion hook
 */
class SVN_Hook_PostRevPropset // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    /** @var SVN_Hooks */
    private $svn_hooks;

    /** @var ReferenceManager */
    private $reference_manager;

    /** @var SvnCommitsDao */
    private $dao;

    public function __construct(SVN_Hooks $svn_hooks, ReferenceManager $reference_manager, SvnCommitsDao $dao)
    {
        $this->svn_hooks         = $svn_hooks;
        $this->reference_manager = $reference_manager;
        $this->dao               = $dao;
    }

    /**
     * Be careful due to a svn bug, this method will be call twice by svn server <= 1.7
     * The first time with the new commit_message as expected
     * The second time with the OLD commit message (oh yeah baby!)
     * http://subversion.tigris.org/issues/show_bug.cgi?id=3085
     *
     * @param String $repository_path
     * @param String $revision
     * @param String $user_name
     * @param String $old_commit_message
     */
    public function update($repository_path, $revision, $user_name, $old_commit_message)
    {
        $project = $this->svn_hooks->getProjectFromRepositoryPath($repository_path);
        $user    = $this->svn_hooks->getUserByName($user_name);
        $message = $this->svn_hooks->getMessageFromRevision($repository_path, $revision);

        $this->dao->updateCommitMessage($project->getID(), $revision, $message);
        $this->removePreviousCrossReferences($project, $revision, $old_commit_message);
        $this->reference_manager->extractCrossRef(
            $message,
            $revision,
            ReferenceManager::REFERENCE_NATURE_SVNREVISION,
            $project->getID(),
            $user->getId()
        );
    }

    private function removePreviousCrossReferences(Project $project, $revision, $old_commit_message)
    {
        $GLOBALS['group_id'] = $project->getID();
        $references          = $this->reference_manager->extractReferences($old_commit_message, (int) $project->getID());
        foreach ($references as $reference_instance) {
            $reference = $reference_instance->getReference();
            \assert($reference instanceof Reference);
            if ($reference) {
                $cross_reference = new CrossReference(
                    $revision,
                    (int) $project->getID(),
                    ReferenceManager::REFERENCE_NATURE_SVNREVISION,
                    '',
                    (int) $reference_instance->getValue(),
                    $reference->getGroupId(),
                    $reference->getNature(),
                    '',
                    ''
                );
                $this->reference_manager->removeCrossReference($cross_reference);
            }
        }
    }
}
