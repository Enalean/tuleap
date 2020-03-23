<?php
/**
 * Copyright (c) Enalean, 2015 - 2018. All Rights Reserved.
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

namespace User\XML\Import;

use UserManager;
use PFUser;

class MappingFileOptimusPrimeTransformer
{

    private static $ALLOWED_ACTIONS = array(
        ToBeActivatedUser::ACTION,
        ToBeCreatedUser::ACTION,
        ToBeMappedUser::ACTION
    );

    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var bool
     */
    private $use_lame_password;

    public function __construct(UserManager $user_manager, $use_lame_password = false)
    {
        $this->user_manager = $user_manager;
        $this->use_lame_password = $use_lame_password;
    }

    /** @return \User\XML\Import\ReadyToBeImportedUsersCollection */
    public function transform(UsersToBeImportedCollection $collection_from_archive, $filename)
    {
        $csv_lines = $this->parseCSVFile($filename);
        return $this->buildCollectionForImport($collection_from_archive, $csv_lines);
    }

    /** @return \User\XML\Import\ReadyToBeImportedUsersCollection */
    public function transformWithoutMap(UsersToBeImportedCollection $collection_from_archive, $default_action)
    {
        $collection_for_import = new ReadyToBeImportedUsersCollection();
        foreach ($collection_from_archive->toArray() as $username => $to_be_imported_user) {
            if ($to_be_imported_user instanceof AlreadyExistingUser) {
                $collection_for_import->add(
                    $to_be_imported_user,
                    $to_be_imported_user->getOriginalUserId(),
                    $to_be_imported_user->getUserName(),
                    $to_be_imported_user->getOriginalLdapId()
                );
            } elseif ($to_be_imported_user instanceof ToBeCreatedUser || $to_be_imported_user instanceof ToBeActivatedUser) {
                $collection_for_import->add(
                    $this->transformUserWithoutMap($collection_from_archive, $username, $default_action, $to_be_imported_user),
                    $to_be_imported_user->getOriginalUserId(),
                    $to_be_imported_user->getUserName(),
                    $to_be_imported_user->getOriginalLdapId()
                );
            } else {
                throw new InvalidUserTypeException("$username: with --automap, user type `" . get_class($to_be_imported_user) . "` is not supported. User: " . $to_be_imported_user->getUserName());
            }
        }

        return $collection_for_import;
    }

    private function transformUserWithoutMap(
        UsersToBeImportedCollection $collection_from_archive,
        $username,
        $action,
        User $to_be_imported_user
    ) {
        if ($to_be_imported_user instanceof ToBeActivatedUser) {
            $action = ToBeActivatedUser::ACTION;
        }
        return $this->transformUser($collection_from_archive, $username, $action, $to_be_imported_user);
    }

    private function buildCollectionForImport(UsersToBeImportedCollection $collection_from_archive, $csv_lines)
    {
        $collection_for_import = new ReadyToBeImportedUsersCollection();
        foreach ($collection_from_archive->toArray() as $username => $to_be_imported_user) {
            if (isset($csv_lines[$username])) {
                $action = $csv_lines[$username];
                $collection_for_import->add(
                    $this->transformUser($collection_from_archive, $username, $action, $to_be_imported_user),
                    $to_be_imported_user->getOriginalUserId(),
                    $to_be_imported_user->getUserName(),
                    $to_be_imported_user->getOriginalLdapId()
                );
            } elseif ($to_be_imported_user instanceof AlreadyExistingUser) {
                $collection_for_import->add(
                    $to_be_imported_user,
                    $to_be_imported_user->getOriginalUserId(),
                    $to_be_imported_user->getUserName(),
                    $to_be_imported_user->getOriginalLdapId()
                );
            } else {
                throw new MissingEntryInMappingFileException("user $username should be in the mapping file");
            }
        }

        return $collection_for_import;
    }

    private function transformUser(
        UsersToBeImportedCollection $collection_from_archive,
        $username,
        $action,
        User $to_be_imported_user
    ) {
        $argument = null;
        if (strpos($action, ':') !== false) {
            list($action, $argument) = explode(':', $action);
        }

        if (! in_array($action, self::$ALLOWED_ACTIONS)) {
            throw new InvalidMappingFileException("Unknown action $action");
        }

        if (! $to_be_imported_user->isActionAllowed($action)) {
            throw new InvalidMappingFileException("Action $action is not allowed for user $username (" .  get_class($to_be_imported_user) . ")");
        }

        if ($action === ToBeMappedUser::ACTION) {
            return $this->getWillBeMappedUser($username, $argument, $collection_from_archive);
        } elseif ($action === ToBeCreatedUser::ACTION) {
            return $this->getWillBeCreatedUser($username, $argument, $to_be_imported_user);
        } elseif ($action === ToBeActivatedUser::ACTION) {
            return new WillBeActivatedUser($this->getExistingUser($username));
        }

        return $to_be_imported_user;
    }

    private function getWillBeMappedUser($username, $mapped_username, $collection_from_archive)
    {
        $mapped_user = $this->getMappedUser($collection_from_archive, $username, $mapped_username);

        return new WillBeMappedUser($username, $mapped_user);
    }

    private function getWillBeCreatedUser($username, $status, ToBeCreatedUser $to_be_imported_user)
    {
        if (! $status) {
            $status = PFUser::STATUS_SUSPENDED;
        }

        if (! in_array($status, WillBeCreatedUser::$ALLOWED_STATUSES)) {
            throw new InvalidMappingFileException("Invalid status [$status] for $username creation. Valid status are S, R and A.");
        }

        return new WillBeCreatedUser(
            $to_be_imported_user->getUserName(),
            $to_be_imported_user->getRealName(),
            $to_be_imported_user->getEmail(),
            $status,
            $to_be_imported_user->getOriginalLdapId(),
            $this->use_lame_password
        );
    }

    private function getExistingUser($username)
    {
        $existing_user = $this->user_manager->getUserByUsername($username);
        if (! $existing_user) {
            throw new InvalidMappingFileException("User with username $username does not exist on the platform");
        }

        return $existing_user;
    }

    private function getMappedUser(UsersToBeImportedCollection $collection, $username, $mapped_username)
    {
        if (! $mapped_username) {
            throw new InvalidMappingFileException("map action for $username must be filled");
        }

        return $this->getExistingUser($mapped_username);
    }

    /**
     * @return array
     */
    private function parseCSVFile($filename)
    {
        if (! is_readable($filename)) {
            throw new MappingFileDoesNotExistException("$filename does not exist");
        }

        $csv = fopen($filename, 'r');
        if ($csv === false) {
            throw new MappingFileDoesNotExistException("$filename does not exist");
        }

        $header = fgetcsv($csv);
        $lines  = array();

        while (($data = fgetcsv($csv)) !== false) {
            $username = $data[0];
            $action   = $data[1];

            if (isset($lines[$username])) {
                throw new InvalidMappingFileException("user $username appears multiple times in mapping file");
            }
            $lines[$username] = $action;
        }

        fclose($csv);

        return $lines;
    }
}
