#!/bin/bash

# renames a class, modifying the filename and all imports accordingly

set -x

old_name=$1
new_name=$2

if [ -x gsed ]; then
  sed_exec='gsed'
else 
  sed_exec='sed'
fi

# Replace imports and class names
files=$(grep -rl  $old_name *)
$sed_exec -i "s/$old_name/$new_name/" $files

# rename file
old_file_full_path=$(find . -name *${old_name}.class.php)
new_file_full_path=$(echo $old_file_full_path | $sed_exec "s/$old_name/$new_name/")
git mv $old_file_full_path $new_file_full_path

# rename test file
old_file_full_path=$(find . -name *${old_name}Test.php)
if [ -n "$old_file_full_path" ]; then
    new_file_full_path=$(echo $old_file_full_path | $sed_exec "s/$old_name/$new_name/")
    git mv $old_file_full_path $new_file_full_path
fi
