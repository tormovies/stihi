<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class UserChangePassword extends Command
{
    protected $signature = 'user:change-password {email : User email} {password? : New password (if omitted, will be prompted)}';
    protected $description = 'Change password for a user by email';

    public function handle(): int
    {
        $email = $this->argument('email');
        $password = $this->argument('password');

        $user = User::query()->where('email', $email)->first();

        if (!$user) {
            $this->error("Пользователь с email «{$email}» не найден.");
            return self::FAILURE;
        }

        $this->info("Пользователь: {$user->name} ({$user->email})");

        if ($password === null || $password === '') {
            $password = $this->ask('Новый пароль');
            if ($password === null || $password === '') {
                $this->error('Пароль не задан.');
                return self::FAILURE;
            }
            $confirm = $this->ask('Повторите пароль');
            if ($password !== $confirm) {
                $this->error('Пароли не совпадают.');
                return self::FAILURE;
            }
        }

        if (strlen($password) < 8) {
            $this->error('Пароль должен быть не короче 8 символов.');
            return self::FAILURE;
        }

        $user->password = Hash::make($password);
        $user->save();

        $this->info('Пароль успешно изменён.');

        return self::SUCCESS;
    }
}
