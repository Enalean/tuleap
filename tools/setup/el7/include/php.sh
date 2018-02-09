_phpPasswordHasher() {
    # ${1}: password

    "${php}" "${tools_dir}/utils/password_hasher.php" -p "${1}"
}
