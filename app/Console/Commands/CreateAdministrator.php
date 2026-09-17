<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdministrator extends Command
{
    protected $signature = 'portfolio:admin {email?}';

    protected $description = 'Create a private administrator with an interactively entered password';

    public function handle(): int
    {
        $email = $this->argument('email') ?? $this->ask('Administrator email');
        $name = $this->ask('Name');
        $password = $this->secret('Password (at least 12 characters, mixed case and numbers)');
        $confirmation = $this->secret('Confirm password');
        $validator = Validator::make(['email' => $email, 'name' => $name, 'password' => $password, 'password_confirmation' => $confirmation], ['email' => 'required|email|unique:users,email', 'name' => 'required|string|max:255', 'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()]]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }
        $user = new User(['name' => $name, 'email' => $email, 'password' => $password]);
        $user->is_admin = true;
        $user->save();
        $this->info('Administrator created. Sign in at the frontend /admin page.');

        return self::SUCCESS;
    }
}
