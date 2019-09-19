<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

class SOAP_WSDLGeneratorFixtures
{

    /**
     * Create a new project
     *
     * This method throw an exception if there is a conflict on names or
     * it there is an error during the creation process.
     * It assumes a couple of things:
     * * The project type is "Project" (Not modifiable)
     * * The template is the default one (project id 100).
     * * There is no "Project description" nor any "Project description
     * * fields" (long desc, patents, IP, other software)
     * * The project services are inherited from the template
     * * There is no trove cat selected
     * * The default Software Policy is "Site exchange policy".
     *
     * Projects are automatically accepted
     *
     * * @todo DO stuff
     *
     * @param String  $requesterLogin Login of the user on behalf of who you create the project
     * @param String  $shortName      Unix name of the project
     * @param String  $realName       Full name of the project
     * @param String  $privacy        Either 'public' or 'private'
     * @param int $templateId Id of template project
     *
     * @return int The ID of newly created project
     */
    public function addProject($requesterLogin, $shortName, $realName, $privacy, $templateId)
    {
    }

    /**
     * @return bool
     */
    public function returnBoolean()
    {
    }

    /**
     * @return ArrayOfString
     */
    public function returnArrayOfString()
    {
    }

    /**
     * @return ArrayOfTrucsZarb
     */
    public function returnUnknownType()
    {
    }

    /**
     * @return ArrayOfPluginTypes
     */
    public function returnArrayOfPluginTypes()
    {
    }
}
