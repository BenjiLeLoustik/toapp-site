<?php

declare(strict_types=1);

namespace App\Controller;

use App\Notification\Helper\NotificationMailerHelper;
use NeoPHP\Component\Controller\Contract\AbstractController;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

#[Route('/notifications', name: 'app_notification')]
class NotificationController extends AbstractController
{
    #[Route('/unsubscribe', name: '_unsubscribe', methods: ['GET'])]
    public function unsubscribe(Request $request, NotificationMailerHelper $notifications): Response
    {
        $type = $notifications->unsubscribe(
            (int) $request->query->get('id', 0),
            (string) $request->query->get('type', ''),
            (string) $request->query->get('signature', '')
        );

        $type !== null
            ? $this->addFlash('success', $this->translate('notification.unsubscribe.flash.done', ['type' => $this->translate($type->translationKey())]))
            : $this->addFlash('error', $this->translate('notification.unsubscribe.flash.invalid'));

        return $this->redirectToRoute($this->getUser() !== null ? 'home_index' : 'app_security_login');
    }
}