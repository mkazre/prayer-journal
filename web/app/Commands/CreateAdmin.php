<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Shield\Entities\User;

/**
 * Non-interactive admin bootstrap for fresh installs and CI:
 *
 *   php spark app:admin --email you@example.com --password 'secret' [--username admin] [--group superadmin]
 *
 * If the email already exists the user is added to the group (and the
 * password is reset only when --password is given).
 */
class CreateAdmin extends BaseCommand
{
    protected $group       = 'Prayer Journal';
    protected $name        = 'app:admin';
    protected $description = 'Creates (or promotes) an administrator account without prompts.';
    protected $options     = [
        '--email'    => 'Login email (required).',
        '--password' => 'Password (min 8 characters). Required for a new account.',
        '--username' => 'Username (defaults to the part of the email before @).',
        '--group'    => 'Group to add: superadmin, admin, instructor or vendor (default superadmin).',
    ];

    public function run(array $params)
    {
        $email    = (string) CLI::getOption('email');
        $password = (string) CLI::getOption('password');
        $group    = (string) (CLI::getOption('group') ?: 'superadmin');
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            CLI::error('Pass a valid --email.');

            return EXIT_ERROR;
        }
        if (! in_array($group, array_keys(config('AuthGroups')->groups), true)) {
            CLI::error('Unknown group "' . $group . '".');

            return EXIT_ERROR;
        }

        $users = auth()->getProvider();
        $user  = $users->findByCredentials(['email' => $email]);
        if ($user === null) {
            if (strlen($password) < 8) {
                CLI::error('A new account needs --password with at least 8 characters.');

                return EXIT_ERROR;
            }
            $username = (string) (CLI::getOption('username') ?: preg_replace('/[^a-z0-9_]/i', '', strstr($email, '@', true)));
            $user     = new User(['username' => $username, 'email' => $email, 'password' => $password, 'active' => 1]);
            if (! $users->save($user)) {
                CLI::error(implode(' ', $users->errors()));

                return EXIT_ERROR;
            }
            $user = $users->findById($users->getInsertID());
            CLI::write('Created user ' . $username . ' <' . $email . '>', 'green');
        } elseif ($password !== '') {
            $user->fill(['password' => $password]);
            $users->save($user);
            CLI::write('Password updated.', 'yellow');
        }

        if (! $user->inGroup($group)) {
            $user->addGroup($group);
        }
        $user->activate();
        CLI::write($email . ' is now in group "' . $group . '".', 'green');

        return EXIT_SUCCESS;
    }
}
