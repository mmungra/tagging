<?php

namespace App\Security;

use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class FileUserProvider implements UserProviderInterface
{
    private string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 2) . '/var/credentials.txt';
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        if (!is_readable($this->file)) {
            throw new \RuntimeException('Credentials file is not readable.');
        }

        $lines = file($this->file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {

            $parts = explode('|', trim($line), 3);

            if (count($parts) !== 3) {
                continue;
            }

            [$username, $password, $roles] = $parts;

            if ($username === $identifier) {

                return new FileUser(
                    $username,
                    $password,
                    array_map('trim', explode(',', $roles))
                );
            }
        }

        $exception = new UserNotFoundException();
        $exception->setUserIdentifier($identifier);

        throw $exception;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof FileUser) {
            throw new \InvalidArgumentException('Unsupported user class.');
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return FileUser::class === $class;
    }
}
