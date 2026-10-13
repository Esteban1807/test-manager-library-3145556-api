<?php

namespace App\Bootstrap\Console;

use App\Application\Ports\PasswordHasher;
use App\Application\Ports\UserRepository;
use App\Domain\User\Role;
use App\Domain\User\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

// Creates the initial admin from ADMIN_USERNAME / ADMIN_PASSWORD (plan D-10). Idempotent.
final class BootstrapAdminCommand extends Command
{
    protected $signature = 'library:bootstrap-admin';

    protected $description = 'Crea el administrador inicial desde las variables de entorno, si todavía no existe.';

    public function handle(UserRepository $users, PasswordHasher $hasher): int
    {
        $username = config('library.admin_username');
        $password = config('library.admin_password');

        if (! is_string($username) || $username === '' || ! is_string($password) || $password === '') {
            $this->error('Faltan ADMIN_USERNAME o ADMIN_PASSWORD en el entorno.');

            return self::FAILURE;
        }

        $admin = User::provision(Str::uuid()->toString(), $username, $hasher->hash($password), Role::Admin);

        if ($users->findByUsername($admin->username()) !== null) {
            $this->info('El administrador ya existe.');

            return self::SUCCESS;
        }

        $users->save($admin);
        $this->info('Administrador creado.');

        return self::SUCCESS;
    }
}
