<?php

declare(strict_types=1);

namespace App\Admin\Widget;

use App\Admin\Helper\ContactAdminHelper;
use NeoPHP\Component\Routing\RoutingManagerInterface;
use NeoPHP\NeoAdmin\Page\Block\LinkList;
use NeoPHP\NeoAdmin\Page\Contract\BlockInterface;
use NeoPHP\NeoAdmin\Page\Contract\WidgetInterface;

class LatestMessagesWidget implements WidgetInterface
{
    public function __construct(
        protected ContactAdminHelper $contact,
        protected RoutingManagerInterface $routing,
    ) {
    }

    public function build(): BlockInterface
    {
        $links = [];

        foreach ($this->contact->messages(null, 5) as [$id, $date, $name, $email, $subject, $status]) {
            $links[] = [
                'label' => sprintf('%s · %s', $name !== '' ? $name : $email, $subject),
                'url' => $this->routing->generate('admin_contact_message', ['id' => $id]),
                'meta' => $status,
            ];
        }

        return new LinkList('Latest messages', $links, 'mail', 'No message yet.', $this->routing->generate('admin_contact'));
    }
}