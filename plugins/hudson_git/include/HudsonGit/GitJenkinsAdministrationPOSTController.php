<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\HudsonGit;

use GitPermissionsManager;
use GitPlugin;
use HTTPRequest;
use Project;
use ProjectManager;
use RuntimeException;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class GitJenkinsAdministrationPOSTController implements DispatchableWithRequest
{
    /**
     * @var ProjectManager
     */
    private $project_manager;

    /**
     * @var GitPermissionsManager
     */
    private $git_permissions_manager;

    /**
     * @var GitJenkinsAdministrationServerAdder
     */
    private $git_jenkins_administration_server_adder;

    public function __construct(
        ProjectManager $project_manager,
        GitPermissionsManager $git_permissions_manager,
        GitJenkinsAdministrationServerAdder $git_jenkins_administration_server_adder
    ) {
        $this->project_manager                         = $project_manager;
        $this->git_permissions_manager                 = $git_permissions_manager;
        $this->git_jenkins_administration_server_adder = $git_jenkins_administration_server_adder;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project = $this->getProjectFromRequest($request);

        if (! $project->usesService(GitPlugin::SERVICE_SHORTNAME)) {
            throw new NotFoundException(dgettext("tuleap-git", "Git service is disabled."));
        }

        $user = $request->getCurrentUser();
        if (! $this->git_permissions_manager->userIsGitAdmin($user, $project)) {
            throw new ForbiddenException(dgettext("tuleap-hudson_git", 'User is not Git administrator.'));
        }

        $provided_url = $request->get('url');
        if ($provided_url === false) {
            throw new RuntimeException(dgettext("tuleap-hudson_git", "Expected jenkins server URL not found"));
        }

        $this->git_jenkins_administration_server_adder->addServerInProject(
            $project,
            trim($provided_url)
        );

        $layout->redirect(
            GitJenkinsAdministrationURLBuilder::buildUrl($project)
        );
    }

    /**
     * @throws NotFoundException
     */
    private function getProjectFromRequest(HTTPRequest $request): Project
    {
        $project_id = $request->get('project_id');
        if ($project_id === false) {
            throw new NotFoundException(dgettext("tuleap-git", "Project not found."));
        }

        $project = $this->project_manager->getProject((int) $project_id);
        if (! $project || $project->isError()) {
            throw new NotFoundException(dgettext("tuleap-git", "Project not found."));
        }

        return $project;
    }
}
