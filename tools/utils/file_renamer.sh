#!/bin/bash

# modifies a file name and php imports of this file

old_filename=$1.class.php
new_filename=$2.class.php

imports=$(grep -rl -e "require.*$old_filename" *)
gsed -i "s/$old_filename/$new_filename/" $imports
