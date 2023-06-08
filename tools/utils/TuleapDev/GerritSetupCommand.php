<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace TuleapDev\TuleapDev;

use Http\Client\Common\Plugin\AuthenticationPlugin;
use Http\Client\Common\PluginClient;
use Http\Message\Authentication\BasicAuth;
use Http\Message\MessageFactory;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use Psr\Http\Client\ClientInterface;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Http\Adapter\Guzzle7\Client;

class GerritSetupCommand extends Command
{
    protected function configure()
    {
        $this->setName('gerrit-setup')
            ->setDescription('Initialize gerrit server for Tuleap usage')
            ->addOption('gerrit-admin-password', 'p', InputOption::VALUE_REQUIRED, 'Password for gerrit administrator')
            ->addOption('gerrit-admin-login', 'u', InputOption::VALUE_OPTIONAL, 'Login name of gerrit administrator (eg. gerrit-admin)', 'gerrit-admin')
            ->addOption('tuleap-server', '', InputOption::VALUE_OPTIONAL, 'Tuleap server name', 'tuleap-web.tuleap-aio-dev.docker')
            ->addOption('gerrit-uri', '', InputOption::VALUE_OPTIONAL, 'Gerrit server URI', 'http://gerrit.tuleap-aio-dev.docker:8080')
            ->addOption('ssh-private-key-path', '', InputOption::VALUE_OPTIONAL, 'Where is stored the codendiadm ssh private key for gerrit', '/var/lib/tuleap/.ssh/id_rsa-gerrit')
            ->addOption('ssh-public-key-path', '', InputOption::VALUE_OPTIONAL, 'Where is stored the codendiadm ssh public key for gerrit', '/var/lib/tuleap/.ssh/id_rsa-gerrit.pub');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('gerrit-admin-password') === null) {
            throw new RuntimeException('--gerrit-admin-password is mandatory');
        }

        $gerrit_server = $input->getOption('gerrit-uri');

        $client = Client::createWithConfig(['timeout' => 5]);

        $authentication       = new BasicAuth($input->getOption('gerrit-admin-login'), $input->getOption('gerrit-admin-password'));
        $authenticationPlugin = new AuthenticationPlugin($authentication);

        $plugin_client = new PluginClient(
            $client,
            [$authenticationPlugin]
        );

        $message_factory = new GuzzleMessageFactory();

        $this->firstLogin($input, $output, $message_factory, $gerrit_server, $client);
        $this->generateSSHKey($input, $output);
        $this->pushSSHKey($input, $output, $message_factory, $gerrit_server, $plugin_client);
        $this->pushReplicationGroup($input, $output, $message_factory, $gerrit_server, $plugin_client);
        $this->pushAdminPermissions($output, $message_factory, $gerrit_server, $plugin_client);
        $this->pairWithGerritServer($input, $output, $gerrit_server);

        return 0;
    }

    private function firstLogin(
        InputInterface $input,
        OutputInterface $output,
        MessageFactory $message_factory,
        string $gerrit_server,
        ClientInterface $client,
    ): void {
        $request  = $message_factory->createRequest(
            'POST',
            $gerrit_server . '/login',
            ['Content-type' => 'application/x-www-form-urlencoded'],
            'username=' . urlencode($input->getOption('gerrit-admin-login')) . '&password=' . urlencode($input->getOption('gerrit-admin-password'))
        );
        $response = $client->sendRequest($request);
        if ($response->getStatusCode() !== 302) {
            throw new RuntimeException(
                'Error (' . $response->getStatusCode() . ') on first login: ' . $response->getBody()->getContents()
            );
        }
        $output->writeln("First Login successful");
    }

    private function generateSSHKey(InputInterface $input, OutputInterface $output): void
    {
        $ssh_key_path = $input->getOption('ssh-private-key-path');
        if (! file_exists($ssh_key_path)) {
            $cmd_output   = [];
            $return_value = -1;
            exec('ssh-keygen -P "" -f ' . escapeshellarg($ssh_key_path), $output_cmd, $return_value);
            if ($return_value !== 0) {
                throw new RuntimeException('Unable to generate ssh key ' . $ssh_key_path . ': ' . implode(PHP_EOL, $cmd_output));
            }
            $output->writeln('SSH key for Tuleap -> Gerrit connexion generated');
        }
        $output->writeln('SSH key for Tuleap -> Gerrit is there');
    }

    private function pushSSHKey(
        InputInterface $input,
        OutputInterface $output,
        MessageFactory $message_factory,
        string $gerrit_server,
        ClientInterface $plugin_client,
    ): void {
        $request  = $message_factory->createRequest(
            'POST',
            $gerrit_server . '/a/accounts/self/sshkeys',
            ['Content-type' => 'text/plain'],
            file_get_contents($input->getOption('ssh-public-key-path'))
        );
        $response = $plugin_client->sendRequest($request);
        if ($response->getStatusCode() !== 201) {
            throw new RuntimeException(
                'Error (' . $response->getStatusCode() . ') on pushing sshkey: ' . $response->getBody()->getContents()
            );
        }
        $output->writeln("SSH key successfully pushed");
    }

    private function pushReplicationGroup(
        InputInterface $input,
        OutputInterface $output,
        MessageFactory $message_factory,
        string $gerrit_server,
        ClientInterface $plugin_client,
    ): void {
        $request = $message_factory->createRequest(
            'PUT',
            $gerrit_server . '/a/groups/' . $input->getOption('tuleap-server') . '-replication',
            ['Content-Type' => 'application/json;charset=UTF-8'],
            json_encode(['visible_to_all' => true])
        );

        $response = $plugin_client->sendRequest($request);
        if ($response->getStatusCode() !== 201) {
            throw new RuntimeException(
                'Error (' . $response->getStatusCode() . ') on creating replication group: ' . $response->getBody()->getContents()
            );
        }
        $output->writeln("Replication group created");

        $this->forceReplicationReload($output, $message_factory, $gerrit_server, $plugin_client);
    }

    private function pushAdminPermissions(
        OutputInterface $output,
        MessageFactory $message_factory,
        string $gerrit_server,
        ClientInterface $plugin_client,
    ): void {
        $admin_group_uuid = $this->getAdministratorGroupUUID($message_factory, $gerrit_server, $plugin_client);
        $permission       = [
            'add' => [
                'refs/meta/*' => [
                    'permissions' => [
                        'read' => [
                            'rules' => [
                                $admin_group_uuid => [
                                    'action' => 'ALLOW',
                                ],
                            ],
                        ],
                        'push' => [
                            'rules' => [
                                $admin_group_uuid => [
                                    'action' => 'ALLOW',
                                ],
                            ],
                        ],
                    ],
                ],
                'refs/*' => [
                    'permissions' => [
                        'forgeAuthor' => [
                            'rules' => [
                                $admin_group_uuid => [
                                    'action' => 'ALLOW',
                                ],
                            ],
                        ],
                        'forgeCommitter' => [
                            'rules' => [
                                $admin_group_uuid => [
                                    'action' => 'ALLOW',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $request = $message_factory->createRequest(
            'POST',
            $gerrit_server . '/a/projects/All-Projects/access',
            ['Content-Type' => 'application/json;charset=UTF-8'],
            json_encode($permission)
        );

        $response = $plugin_client->sendRequest($request);
        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException(
                'Error (' . $response->getStatusCode() . ') on creating replication group: ' . $response->getBody()->getContents()
            );
        }
        $output->writeln("Permissions on All-Projects updated");
    }

    private function getAdministratorGroupUUID(MessageFactory $message_factory, string $gerrit_server, ClientInterface $plugin_client): string
    {
        $request  = $message_factory->createRequest('GET', $gerrit_server . '/a/groups/Administrators');
        $response = $plugin_client->sendRequest($request);
        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException(
                'Error (' . $response->getStatusCode() . ') unable to get Administrator group def: ' . $response->getBody()->getContents()
            );
        }

        $group = $this->getJsonFromResponse($response->getBody()->getContents());
        return $group['id'];
    }

    private function getJsonFromResponse(string $body): array
    {
        return json_decode(substr($body, 5), true);
    }

    private function forceReplicationReload(
        OutputInterface $output,
        MessageFactory $message_factory,
        string $gerrit_server,
        ClientInterface $plugin_client,
    ): void {
        $request = $message_factory->createRequest(
            'POST',
            $gerrit_server . '/a/plugins/replication/gerrit~reload',
            ['Content-Type' => 'application/json;charset=UTF-8'],
            json_encode(['visible_to_all' => true])
        );

        $response = $plugin_client->sendRequest($request);
        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException(
                'Error (' . $response->getStatusCode() . ') on creating replication group: ' . $response->getBody()->getContents()
            );
        }
        $output->writeln("Replication reloaded");
    }

    private function pairWithGerritServer(InputInterface $input, OutputInterface $output, string $gerrit_server): void
    {
        $url_parts    = parse_url($gerrit_server);
        $cmd_output   = [];
        $return_value = -1;
        exec(
            'ssh -i ' . escapeshellarg($input->getOption('ssh-private-key-path')) . ' -oStrictHostKeyChecking=no -p 29418 ' . escapeshellarg($input->getOption('gerrit-admin-login') . '@' . $url_parts['host']) . ' gerrit version',
            $cmd_output,
            $return_value
        );
        if ($return_value !== 0) {
            throw new RuntimeException('Unable to connect to gerrit via ssh ' . implode(PHP_EOL, $cmd_output));
        }
        $output->writeln('SSH connection done with ' . $cmd_output[0]);
    }
}
