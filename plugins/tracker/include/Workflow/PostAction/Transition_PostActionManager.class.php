<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * Manager for PostActions
 */
class Transition_PostActionManager
{

    /**
     * Process the artifact functions
     *
     * @param Transition      $transition   The transition
     * @param Codendi_Request $request      The data from the user
     * @param PFUser            $current_user The current user
     *
     * @return void
     */
    public function process(Transition $transition, Codendi_Request $request, PFUser $current_user)
    {
        $tpaf = $this->getPostActionFactory();

        // Create new post-action
        if ($request->existAndNonEmpty('add_postaction')) {
            $tpaf->addPostAction($transition, $request->get('add_postaction'));
        }

        // Loop over defined actions and update them if relevant
        foreach ($transition->getAllPostActions() as $post_action) {
            $post_action->process($request);
        }
    }

    /**
     * Wrapper for Transition_PostActionFactory
     *
     * @return Transition_PostActionFactory
     */
    public function getPostActionFactory()
    {
        return new Transition_PostActionFactory();
    }
}
