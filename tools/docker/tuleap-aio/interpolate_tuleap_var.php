#!/usr/bin/env php
<?php

$var = $argv[2];

include_once($argv[1]);
echo $$var;
