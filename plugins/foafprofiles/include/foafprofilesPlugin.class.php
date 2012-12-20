<?php

/**
 * foafprofilesPlugin Class
 *
 * Copyright 2011, Olivier Berger & Institut Telecom
 *
 * This program was developped in the frame of the COCLICO project
 * (http://www.coclico-project.org/) with financial support of the Paris
 * Region council.
 *
 * This file is part of FusionForge. FusionForge is free software;
 * you can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the Licence, or (at your option)
 * any later version.
 *
 * FusionForge is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with FusionForge; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

require_once 'common/include/rdfutils.php';

class foafprofilesPlugin extends Plugin {
	public function __construct($id=0) {
		$this->Plugin($id) ;
		$this->name = "foafprofiles";
		$this->text = "User FOAF Profiles"; // To show in the tabs, use...
		$this->_addHook("script_accepted_types");
		$this->_addHook("content_negociated_user_home");

	}

	/**
	 * Declares itself as accepting RDF XML on /users
	 * @param unknown_type $params
	 */
	function script_accepted_types (&$params) {
		$script = $params['script'];
		if ($script == 'user_home') {
			$params['accepted_types'][] = 'application/rdf+xml';
		}
	}

	/**
	 * Outputs user's FOAF profile
	 * @param unknown_type $params
	 */
	function content_negociated_user_home (&$params) {
		$username = $params['username'];
		$accept = $params['accept'];

		if($accept == 'application/rdf+xml') {
				$params['content_type'] = 'application/rdf+xml';

				$user_obj = user_get_object_by_name($username);

				$user_real_name = $user_obj->getRealName();
				$user_email = $user_obj->getEmail();
				$mbox = 'mailto:'.$user_email;
				$mbox_sha1sum = sha1($mbox);

				$projects = $user_obj->getGroups() ;
				sortProjectList($projects) ;
				$roles = RBACEngine::getInstance()->getAvailableRolesForUser($user_obj) ;
				sortRoleList($roles) ;
				
				// Construct an ARC2_Resource containing the project's RDF (DOAP) description
				$ns = array(
						'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
						'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
						'foaf' => 'http://xmlns.com/foaf/0.1/',
						'sioc' => 'http://rdfs.org/sioc/ns#', 
						'doap' => 'http://usefulinc.com/ns/doap#',
						'dcterms' => 'http://purl.org/dc/terms/',
						'planetforge' => 'http://coclico-project.org/ontology/planetforge#'
				);
				 
				$conf = array(
						'ns' => $ns
				);
				
				// First, let's deal with the account
				$account_res = ARC2::getResource($conf);
				$account_uri = util_make_url_u($username, $user_obj->getID());
				$account_uri = rtrim($account_uri,'/');
				$person_uri = $account_uri . '#person';
				
				$account_res->setURI( $account_uri );
				// $account_res->setRel('rdf:type', 'foaf:OnlineAccount');
				rdfutils_setPropToUri($account_res, 'rdf:type', 'foaf:OnlineAccount');
				rdfutils_setPropToUri($account_res, 'foaf:accountServiceHomepage', $account_uri . '/');
				$account_res->setProp('foaf:accountName', $username);
				rdfutils_setPropToUri($account_res, 'sioc:account_of', $person_uri);
				rdfutils_setPropToUri($account_res, 'foaf:accountProfilePage', $account_uri);

				$groups_index = array();
				$projects_index = array();
				$roles_index = array();
				
				$usergroups_uris = array();
				// see if there were any groups
				if (count($projects) >= 1) {
					foreach ($projects as $p) {
						// TODO : report also private projects if authenticated, for instance through OAuth
						if($p->isPublic()) {
							$project_link = util_make_link_g ($p->getUnixName(),$p->getID(),$p->getPublicName());
							$project_uri = util_make_url_g ($p->getUnixName(),$p->getID());
							// sioc:UserGroups for all members of a project are named after /projects/A_PROJECT/members/
							$usergroup_uri = $project_uri .'members/';

							$role_names = array();
							
							$usergroups_uris[] = $usergroup_uri;
							
							$usergroup_res = ARC2::getResource($conf);
							$usergroup_res->setURI( $usergroup_uri );
							rdfutils_setPropToUri($usergroup_res, 'rdf:type', 'sioc:UserGroup');
							rdfutils_setPropToUri($usergroup_res, 'sioc:usergroup_of', $project_uri);

							$roles_uris = array();
							foreach ($roles as $r) {
								if ($r instanceof RoleExplicit
										&& $r->getHomeProject() != NULL
										&& $r->getHomeProject()->getID() == $p->getID()) {
									$role_names[$r->getID()] = $r->getName() ;
									$role_uri = $project_uri .'roles/'.$r->getID();
									
									$roles_uris[] = $role_uri;
								}
							}
							rdfutils_setPropToUri($usergroup_res, 'planetforge:group_has_function', $roles_uris);

							$project_res = ARC2::getResource($conf);
							$project_res->setURI( $project_uri );
							rdfutils_setPropToUri($project_res, 'rdf:type', 'planetforge:ForgeProject');
							$project_res->setProp('doap:name', $p->getUnixName());
							
							$projects_index = ARC2::getMergedIndex($projects_index, $project_res->index);
							
							
							foreach ($role_names as $id => $name) {
								$role_res = ARC2::getResource($conf);
								$role_res->setURI( $project_uri .'roles/'.$id );
								rdfutils_setPropToUri($role_res, 'rdf:type', 'sioc:Role');
								$role_res->setProp('sioc:name', $name);
								
								$roles_index = ARC2::getMergedIndex($roles_index, $role_res->index);
							}
							
							$groups_index = ARC2::getMergedIndex($groups_index, $usergroup_res->index);
															
						}
					}
				} // end if groups
				rdfutils_setPropToUri($account_res, 'sioc:member_of', $usergroups_uris);
				
				// next, deal with the person
				$person_res = ARC2::getResource($conf);
				
				$person_res->setURI( $person_uri );
				rdfutils_setPropToUri($person_res, 'rdf:type', 'foaf:Person');
				$person_res->setProp('foaf:name', $user_real_name);
				rdfutils_setPropToUri($person_res, 'foaf:holdsAccount', $account_uri);
				$person_res->setProp('foaf:mbox_sha1sum', $mbox_sha1sum);
				
				// merge the two sets of triples
				$merged_index = ARC2::getMergedIndex($account_res->index, $person_res->index);
				$merged_index = ARC2::getMergedIndex($merged_index, $groups_index);
				$merged_index = ARC2::getMergedIndex($merged_index, $projects_index);
				$merged_index = ARC2::getMergedIndex($merged_index, $roles_index);
      			
    			$conf = array(
    					'ns' => $ns,
    					'serializer_type_nodes' => true
    			);
    				
    			$ser = ARC2::getRDFXMLSerializer($conf);
    				
    			/* Serialize a resource index */
    			$doc = $ser->getSerializedIndex($merged_index);
    			
    			$params['content'] = $doc . "\n";
		}
	}
}

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
