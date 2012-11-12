#/bin/bash
git config -f project.config --add access.refs/heads/*.Read 'group Registered Users'
ssh gerrit gerrit gsql --format JSON -c "'SELECT group_uuid FROM account_groups WHERE name=\"instagram-admin\"'"
