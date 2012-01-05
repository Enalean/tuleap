<?php

class SOAP_WSDLGeneratorFixtures {

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
     * @param String  $requesterLogin Login of the user on behalf of who you create the project
     * @param String  $shortName      Unix name of the project
     * @param String  $realName       Full name of the project
     * @param String  $privacy        Either 'public' or 'private'
     * @param Integer $templateId     Id of template project
     *
     * @return Integer The ID of newly created project
     */
    public function addProject($requesterLogin, $shortName, $realName, $privacy, $templateId) {
        
    }

}

?>
