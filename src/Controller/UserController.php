<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Project;
use App\Entity\User;
use App\Helper\View\FormatNumberViewHelper;
use App\User\Helper\UserProfileHelper;
use NeoPHP\Component\Api\Attribute\MapPagination;
use NeoPHP\Component\Api\Pagination\PageRequest;
use NeoPHP\Component\Controller\Contract\AbstractController;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Package\Orm\Contract\EntityManagerInterface;

#[Route('/users', name: 'user_')]
class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserProfileHelper $profiles,
    ) {
    }

    #[Route('/{slug}', name: 'show', methods: ['GET'])]
    public function show(
        string $slug,
        #[MapPagination(defaultLimit: 9, maxLimit: 48)]
        PageRequest $pageRequest,
    ): Response {
        $viewer = $this->viewer();
        $profile = $this->profiles->findVisible($slug, $viewer);

        if ($profile === null) {
            throw $this->createNotFoundException('The user "{slug}" does not exist.', ['slug' => $slug]);
        }

        $isOwner = $this->profiles->isOwner($profile, $viewer);

        return $this->render('pages/user/show.html.twig', [
            'profile' => $profile,
            'isOwner' => $isOwner,
            'privacy' => $this->profiles->privacy($profile),
            'followers' => $this->profiles->countFollowers($profile),
            'following' => $this->profiles->countFollowing($profile),
            'isFollowing' => $this->profiles->isFollowing($viewer, $profile),
            'projects' => $this->paginate(
                $this->entityManager->getRepository(Project::class)->createProfileQueryBuilder($profile, $isOwner),
                $pageRequest
            ),
        ]);
    }

    #[Route('/api/{slug}/follow', name: 'api_follow', methods: ['POST'])]
    public function api_follow(string $slug, FormatNumberViewHelper $formatNumber): Response
    {
        $viewer = $this->viewer();

        if ($viewer === null) {
            return $this->json(['success' => false], 401);
        }

        $profile = $this->profiles->findVisible($slug, $viewer);

        if ($profile === null) {
            return $this->json(['success' => false], 404);
        }

        if ($this->profiles->isOwner($profile, $viewer)) {
            return $this->json(['success' => false], 400);
        }

        $following = $this->profiles->toggleFollow($viewer, $profile);

        return $this->json([
            'success' => true,
            'following' => $following,
            'followers' => $formatNumber->__invoke($this->profiles->countFollowers($profile)),
        ]);
    }

    private function viewer(): ?User
    {
        $user = $this->getUser();

        return $user instanceof User ? $user : null;
    }
}