<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateUserCommand extends Command
{
    protected $signature = 'app:create-user {email} {password} {role=admin} {name=Administrator}';

    protected $description = 'Creates a user with the given role';

    public function handle(): int
    {
        $data = [
            'email' => $this->argument('email'),
            'password' => $this->argument('password'),
            'role' => $this->argument('role'),
            'name' => $this->argument('name'),
        ];

        $validator = Validator::make($data, [
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(array_keys(User::ROLE_LEVELS))],
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create($data);

        $this->info(sprintf('User %s created with role %s.', $user->email, $user->role));

        return self::SUCCESS;
    }
}
