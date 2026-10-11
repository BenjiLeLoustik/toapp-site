<?php

declare(strict_types=1);

namespace App\Admin\Widget;

use App\Admin\Helper\ProjectAdminHelper;
use App\Project\Enum\ProjectStatusEnum;
use NeoPHP\Component\Routing\RoutingManagerInterface;
use NeoPHP\NeoAdmin\Page\Block\LinkList;
use NeoPHP\NeoAdmin\Page\Contract\BlockInterface;
use NeoPHP\NeoAdmin\Page\Contract\WidgetInterface;

class LatestProjectsWidget implements WidgetInterface
{
    public function __construct(
        protected ProjectAdminHelper $projects,
        protected RoutingManagerInterface $routing,
    ) {
    }

    public function build(): BlockInterface
    {
        $links = [];

        foreach ($this->projects->list(null, ProjectStatusEnum::ALL, 5) as $project) {
            $links[] = [
                'label' => sprintf('%s · %s', $project['name'], $project['owner']),
                'url' => $this->routing->generate('admin_project_show', ['id' => $project['id']]),
                'meta' => $project['status'],
            ];
        }

        return new LinkList('Latest projects', $links, 'box', 'No project yet.', $this->routing->generate('admin_project'));
    }
}