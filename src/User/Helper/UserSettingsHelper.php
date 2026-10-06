<?php

declare(strict_types=1);

namespace App\User\Helper;

use App\Entity\User;
use App\Entity\UserNotificationSetting;
use App\Entity\UserPreference;
use App\Entity\UserPrivacySetting;
use App\User\Enum\UserDateFormatEnum;
use App\User\Enum\UserDigestFrequencyEnum;
use App\User\Enum\UserNumberFormatEnum;
use NeoPHP\Component\Http\Request\UploadedFile;
use NeoPHP\Component\Upload\Contract\UploaderInterface;
use NeoPHP\Component\Upload\Exception\UploadException;
use NeoPHP\Package\Orm\Contract\EntityManagerInterface;
use NeoPHP\Package\Security\Hasher\UserPasswordHasher;
use NeoPHP\Package\Translation\Contract\TranslatorInterface;

class UserSettingsHelper
{
    public const AVATAR_MAX_SIZE = 2097152;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasher $passwordHasher,
        private UploaderInterface $uploader,
        private TranslatorInterface $translator,
        private UserPathHelper $paths,
    ) {
    }

    public function getPreference(User $user): UserPreference
    {
        return $this->findOrCreate(UserPreference::class, $user);
    }

    public function getPrivacy(User $user): UserPrivacySetting
    {
        return $this->findOrCreate(UserPrivacySetting::class, $user);
    }

    public function getNotifications(User $user): UserNotificationSetting
    {
        return $this->findOrCreate(UserNotificationSetting::class, $user);
    }

    public function updateProfile(User $user, array $data, ?UploadedFile $avatar, bool $removeAvatar): array
    {
        $errors = [];
        $firstname = trim((string) ($data['firstname'] ?? ''));
        $lastname = trim((string) ($data['lastname'] ?? ''));
        $username = trim((string) ($data['username'] ?? ''));
        $location = trim((string) ($data['location'] ?? ''));
        $website = trim((string) ($data['website'] ?? ''));
        $biography = trim((string) ($data['biography'] ?? ''));

        if ($firstname === '' || mb_strlen($firstname) > 50) {
            $errors['firstname'] = $this->trans('settings.errors.firstname');
        }

        if ($lastname === '' || mb_strlen($lastname) > 50) {
            $errors['lastname'] = $this->trans('settings.errors.lastname');
        }

        if (preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username) !== 1) {
            $errors['username'] = $this->trans('settings.errors.username_format');
        } else {
            $existing = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);

            if ($existing !== null && $existing->getId() !== $user->getId()) {
                $errors['username'] = $this->trans('settings.errors.username_taken');
            }
        }

        if (mb_strlen($location) > 100) {
            $errors['location'] = $this->trans('settings.errors.location');
        }

        if ($website !== '' && (mb_strlen($website) > 255 || preg_match('#^https?://#i', $website) !== 1 || filter_var($website, FILTER_VALIDATE_URL) === false)) {
            $errors['website'] = $this->trans('settings.errors.website');
        }

        if (mb_strlen($biography) > 512) {
            $errors['biography'] = $this->trans('settings.errors.biography');
        }

        if ($errors !== []) {
            return $errors;
        }

        if ($avatar !== null && $avatar->getError() !== UPLOAD_ERR_NO_FILE) {
            try {
                $this->updateAvatar($user, $avatar);
            } catch (UploadException) {
                return ['avatar' => $this->trans('settings.errors.avatar')];
            }
        } elseif ($removeAvatar) {
            $this->removeAvatar($user);
        }

        $user
            ->setFirstname($firstname)
            ->setLastname($lastname)
            ->setUsername($username)
            ->setSlug(strtolower($username))
            ->setLocation($location !== '' ? $location : null)
            ->setWebsite($website !== '' ? $website : null)
            ->setBiography($biography !== '' ? $biography : null);

        $this->entityManager->flush();

        return [];
    }

    public function updateAvatar(User $user, UploadedFile $avatar): string
    {
        $path = $this->uploader->store($avatar, $this->paths->avatarDirectory($user), ['max_size' => self::AVATAR_MAX_SIZE]);

        $this->uploader->delete($user->getAvatar());
        $user->setAvatar($path);

        return $path;
    }

    public function removeAvatar(User $user): void
    {
        $this->uploader->delete($user->getAvatar());
        $user->setAvatar(null);
    }

    public function updateEmail(User $user, array $data): array
    {
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['current_password'] ?? '');

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email) > 180) {
            return ['email' => $this->trans('settings.errors.email_invalid')];
        }

        $existing = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if ($existing !== null && $existing->getId() !== $user->getId()) {
            return ['email' => $this->trans('settings.errors.email_taken')];
        }

        if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            return ['current_password' => $this->trans('settings.errors.password_invalid')];
        }

        $user->setEmail($email);
        $this->entityManager->flush();

        return [];
    }

    public function updatePassword(User $user, array $data): array
    {
        $current = (string) ($data['current_password'] ?? '');
        $new = (string) ($data['new_password'] ?? '');
        $confirmation = (string) ($data['new_password_confirmation'] ?? '');

        if (!$this->passwordHasher->isPasswordValid($user, $current)) {
            return ['current_password' => $this->trans('settings.errors.password_invalid')];
        }

        if (mb_strlen($new) < 8 || preg_match('/[A-Z]/', $new) !== 1 || preg_match('/[0-9]/', $new) !== 1) {
            return ['new_password' => $this->trans('settings.errors.password_weak')];
        }

        if ($new !== $confirmation) {
            return ['new_password_confirmation' => $this->trans('settings.errors.password_mismatch')];
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $new));
        $this->entityManager->flush();

        return [];
    }

    public function updatePreference(User $user, array $data, array $themes, array $accents, array $scales, array $locales): UserPreference
    {
        $preference = $this->getPreference($user);
        $timezone = (string) ($data['timezone'] ?? '');

        $preference
            ->setTheme(in_array($data['theme'] ?? null, $themes, true) ? $data['theme'] : $preference->getTheme())
            ->setAccent(in_array($data['accent'] ?? null, $accents, true) ? $data['accent'] : $preference->getAccent())
            ->setScale(in_array($data['scale'] ?? null, $scales, true) ? $data['scale'] : $preference->getScale())
            ->setLocale(in_array($data['locale'] ?? null, $locales, true) ? $data['locale'] : $preference->getLocale())
            ->setDateFormat(UserDateFormatEnum::tryFrom((string) ($data['date_format'] ?? '')) ?? $preference->getDateFormat())
            ->setNumberFormat(UserNumberFormatEnum::tryFrom((string) ($data['number_format'] ?? '')) ?? $preference->getNumberFormat())
            ->setTimezone(in_array($timezone, \DateTimeZone::listIdentifiers(), true) ? $timezone : $preference->getTimezone());

        $this->entityManager->flush();

        return $preference;
    }

    public function updateNotifications(User $user, array $data): void
    {
        $this->getNotifications($user)
            ->setEmailProjectLikes($this->bool($data, 'email_project_likes'))
            ->setEmailProjectFavorites($this->bool($data, 'email_project_favorites'))
            ->setEmailNewsletter($this->bool($data, 'email_newsletter'))
            ->setDigestFrequency(UserDigestFrequencyEnum::tryFrom((string) ($data['digest_frequency'] ?? '')) ?? UserDigestFrequencyEnum::WEEKLY);

        $this->entityManager->flush();
    }

    public function updatePrivacy(User $user, array $data): void
    {
        $this->getPrivacy($user)
            ->setProfilePublic($this->bool($data, 'profile_public'))
            ->setShowEmail($this->bool($data, 'show_email'))
            ->setShowLocation($this->bool($data, 'show_location'))
            ->setShowStats($this->bool($data, 'show_stats'));

        $this->entityManager->flush();
    }

    public function deactivate(User $user): void
    {
        $user->setDeactivatedAt(new \DateTime());
        $this->entityManager->flush();
    }

    public function checkPassword(User $user, string $password): bool
    {
        return $this->passwordHasher->isPasswordValid($user, $password);
    }

    public function anonymize(User $user): void
    {
        $id = (string) $user->getId();

        $this->removeAvatar($user);

        $user
            ->setFirstname('Deleted')
            ->setLastname('User')
            ->setUsername('deleted_' . $id)
            ->setSlug('deleted-' . $id)
            ->setEmail('deleted-' . $id . '-' . bin2hex(random_bytes(4)) . '@deleted.invalid')
            ->setPassword($this->passwordHasher->hashPassword($user, bin2hex(random_bytes(32))))
            ->setBiography(null)
            ->setLocation(null)
            ->setWebsite(null)
            ->setCertified(false)
            ->setRoles([])
            ->setDeletedAt(new \DateTime());

        $this->entityManager->flush();
    }

    private function findOrCreate(string $class, User $user): object
    {
        $entity = $this->entityManager->getRepository($class)->findOneBy(['user' => $user]);

        if ($entity === null) {
            $entity = new $class();
            $entity->setUser($user);

            $this->entityManager->persist($entity);
            $this->entityManager->flush();
        }

        return $entity;
    }

    private function bool(array $data, string $key): bool
    {
        return in_array($data[$key] ?? '0', ['1', 'on', 'true', 1, true], true);
    }

    private function trans(string $key): string
    {
        return $this->translator->translate($key);
    }
}