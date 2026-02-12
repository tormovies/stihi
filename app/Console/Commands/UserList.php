<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UserList extends Command
{
    protected $signature = 'user:list';
    protected $description = 'List all users (id, name, email) to see admin account';

    public function handle(): int
    {
        $users = User::query()->orderBy('id')->get(['id', 'name', 'email']);

        if ($users->isEmpty()) {
            $this->warn('Нет пользователей в базе.');
            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Имя', 'Email'],
            $users->map(fn (User $u) => [$u->id, $u->name, $u->email])->toArray()
        );

        return self::SUCCESS;
    }
}
