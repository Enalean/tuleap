<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ForgeUpgrade;

use Tuleap\ForgeUpgrade\Bucket\BucketApiNotFoundException;
use Psr\Log\LoggerInterface;
use Tuleap\ForgeUpgrade\Bucket\BucketDb;

/**
 * A bucket is a migration scenario
 */
abstract class Bucket // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
{
    protected LoggerInterface $log;

    protected BucketDb $api;

    protected string $path = '';
    private string $id;

    final public function __construct(LoggerInterface $logger, BucketDb $api)
    {
        $this->log = $logger;
        $this->api = $api;
    }

    /**
     * @deprecated Use $this->api directly instead
     * @throws BucketApiNotFoundException
     */
    public function getApi(string $key): BucketDb
    {
        if (! is_subclass_of($key, BucketDb::class)) {
            throw new BucketApiNotFoundException('API "' . $key . '" not found');
        }

        return $this->api;
    }

    /**
     * Return a string with the description of the upgrade
     *
     * @return string
     */
    abstract public function description();

    /**
     * Allow to define a dependency list
     *
     * @return array
     */
    public function dependsOn()
    {
        return [];
    }

    /**
     * Ensure the package is OK before running Up method
     *
     * Use this method add your own pre-conditions.
     * This method aims to verify stuff needed by the up method it doesn't
     * target a global validation of the application.
     *
     * This method MUST be safe (doesn't modify the system and runnable several
     * time)
     *
     * If an error is detected, this method should throw an Exception and this
     * will stop further processing. So only throw an Exception if you detect
     * that something will go wrong during 'up' method execution.
     * For instance:
     * Your 'up' method creates a table but this table already exists.
     * -> This should not throw an exception.
     * -> But if:
     *    - your up method rely on a given field in the table
     *    - this field is not present in the existing table
     *    - you doesn't create the field in 'up'
     * -> This should throw an exception
     *
     * @return void
     */
    public function preUp()
    {
    }

    /**
     * Perform the upgrade
     *
     * @return void
     */
    abstract public function up();

    /**
     * Ensure the package is OK after running Up method
     *
     * Use this method add your own post-conditions.
     * This method aims to verify that what the migration should bring is here.
     *
     * This method MUST be safe (doesn't modify the system and runnable several
     * time)
     *
     * If an error is detected, this method should throw an Exception
     *
     * @return void
     */
    public function postUp()
    {
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
