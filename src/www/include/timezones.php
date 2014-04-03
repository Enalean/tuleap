<?php

function is_valid_timezone($timezone) {
    $collection = new Account_TimezonesCollection();

    return $collection->isValidTimezone($timezone);
}