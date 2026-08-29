<?php

namespace App\Modules\Api\Controllers;

use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;

class AuthApiController extends BaseApiController
{
    public function register()
    {
        $rules = [
            'username' => 'required|min_length[3]|max_length[30]|is_unique[users.username]',
            'email'    => 'required|valid_email|is_unique[auth_identities.secret]',
            'password' => 'required|min_length[8]',
        ];
        if (! $this->validateData($this->request->getJSON(true) ?? [], $rules)) {
            return $this->fail(implode(' ', $this->validator->getErrors()), 422);
        }

        $data = $this->request->getJSON(true);

        $user = new User([
            'username' => $data['username'],
            'email'    => $data['email'],
            'password' => $data['password'],
        ]);

        $users = new UserModel();
        $users->save($user);
        $user = $users->findById($users->getInsertID());
        $user->addGroup('student');

        return $this->issueToken($user, 201);
    }

    public function login()
    {
        $data     = $this->request->getJSON(true) ?? [];
        $email    = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        $users = new UserModel();
        $user  = $users->findByCredentials(['email' => $email]);

        if (! $user || ! password_verify($password, $user->password_hash)) {
            return $this->fail('Invalid credentials.', 401);
        }

        return $this->issueToken($user, 200);
    }

    public function me()
    {
        $user = $this->currentUser();
        if (! $user) {
            return $this->fail('Unauthorized.', 401);
        }

        return $this->ok([
            'id'       => $user->id,
            'username' => $user->username,
            'email'    => $user->email,
            'groups'   => $user->getGroups(),
        ]);
    }

    public function logout()
    {
        $user  = $this->currentUser();
        $token = $this->request->getHeaderLine('Authorization');
        $token = trim(str_ireplace('Bearer', '', $token));
        if ($user && $token) {
            $user->revokeAccessToken($token);
        }

        return $this->ok(['message' => 'Logged out.']);
    }

    private function issueToken($user, int $code)
    {
        $token = $user->generateAccessToken('mobile');

        return $this->ok([
            'token' => $token->raw_token,
            'user'  => [
                'id'       => $user->id,
                'username' => $user->username,
                'email'    => $user->email,
                'groups'   => $user->getGroups(),
            ],
        ], $code);
    }
}
