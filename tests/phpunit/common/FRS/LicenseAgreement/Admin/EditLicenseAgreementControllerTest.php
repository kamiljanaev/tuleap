<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\FRS\LicenseAgreement\Admin;

use Mockery;
use PHPUnit\Framework\TestCase;
use ProjectManager;
use TemplateRendererFactory;
use Tuleap\FRS\FRSPermissionManager;
use Tuleap\FRS\LicenseAgreement\DefaultLicenseAgreement;
use Tuleap\FRS\LicenseAgreement\LicenseAgreement;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Templating\Mustache\MustacheEngine;

class EditLicenseAgreementControllerTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var LicenseAgreementDisplayController
     */
    private $controller;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectManager
     */
    private $project_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Project
     */
    private $project;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\ServiceFile
     */
    private $service_file;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TemplateRendererFactory
     */
    private $renderer_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|FRSPermissionManager
     */
    private $permissions_manager;
    /**
     * @var \HTTPRequest
     */
    private $request;
    /**
     * @var \PFUser
     */
    private $current_user;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LicenseAgreementFactory
     */
    private $factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|BaseLayout
     */
    private $layout;

    protected function setUp(): void
    {
        $this->layout = Mockery::mock(BaseLayout::class);

        $this->current_user = new \PFUser(['language_id' => 'en_US']);

        $this->request = new \HTTPRequest();
        $this->request->setCurrentUser($this->current_user);

        $this->project_manager = Mockery::mock(ProjectManager::class);
        $this->service_file = Mockery::mock(\ServiceFile::class, ['displayFRSHeader' => 'foo']);
        $this->project = Mockery::mock(\Project::class, ['isError' => false, 'getID' => '101']);
        $this->project->shouldReceive('getFileService')->andReturn($this->service_file)->byDefault();
        $this->project_manager->shouldReceive('getProject')->with('101')->andReturns($this->project);

        $this->renderer_factory = Mockery::mock(TemplateRendererFactory::class);

        $this->permissions_manager = Mockery::mock(FRSPermissionManager::class);
        $this->permissions_manager->shouldReceive('isAdmin')->with($this->project, $this->current_user)->andReturnTrue()->byDefault();

        $this->factory = Mockery::mock(LicenseAgreementFactory::class);

        $this->controller = new EditLicenseAgreementController(
            $this->project_manager,
            $this->renderer_factory,
            $this->permissions_manager,
            $this->factory,
            Mockery::mock(\CSRFSynchronizerToken::class),
            Mockery::spy(IncludeAssets::class),
        );
    }

    public function testItRendersThePageHeader(): void
    {
        $header_renderer = Mockery::mock(MustacheEngine::class);
        $header_renderer->shouldReceive('renderToPage')->with('toolbar-presenter', Mockery::any())->once();
        $this->renderer_factory->shouldReceive('getRenderer')->with(Mockery::on(static function (string $path) {
            return realpath($path) === realpath(__DIR__ . '/../../../../../../src/templates/frs');
        }))->andReturn($header_renderer);

        $content_renderer = Mockery::mock(MustacheEngine::class);
        $content_renderer->shouldReceive('renderToPage')->with('edit-license-agreement', Mockery::any())->once();
        $this->renderer_factory->shouldReceive('getRenderer')->with(Mockery::on(static function (string $path) {
            return realpath($path) === realpath(__DIR__ . '/../../../../../../src/common/FRS/LicenseAgreement/Admin/templates');
        }))->andReturn($content_renderer);

        $this->layout->shouldReceive('includeFooterJavascriptFile');
        $this->layout->shouldReceive('footer');

        $this->factory->shouldReceive('getLicenseAgreementById')->with($this->project, 1)->andReturn(new LicenseAgreement(1, 'some title', 'some content'));

        $this->controller->process($this->request, $this->layout, ['project_id' => '101', 'id' => '1']);
    }

    public function testItRendersTheDefaultSiteAgreementInReadOnly(): void
    {
        $header_renderer = Mockery::mock(MustacheEngine::class);
        $header_renderer->shouldReceive('renderToPage')->with('toolbar-presenter', Mockery::any())->once();
        $this->renderer_factory->shouldReceive('getRenderer')->with(Mockery::on(static function (string $path) {
            return realpath($path) === realpath(__DIR__ . '/../../../../../../src/templates/frs');
        }))->andReturn($header_renderer);

        $content_renderer = Mockery::mock(MustacheEngine::class);
        $content_renderer->shouldReceive('renderToPage')->with('view-default-license-agreement', Mockery::any())->once();
        $this->renderer_factory->shouldReceive('getRenderer')->with(Mockery::on(static function (string $path) {
            return realpath($path) === realpath(__DIR__ . '/../../../../../../src/common/FRS/LicenseAgreement/Admin/templates');
        }))->andReturn($content_renderer);

        $this->layout->shouldReceive('includeFooterJavascriptFile');
        $this->layout->shouldReceive('footer');

        $this->factory->shouldReceive('getLicenseAgreementById')->with($this->project, 0)->andReturn(new DefaultLicenseAgreement());

        $this->controller->process($this->request, $this->layout, ['project_id' => '101', 'id' => '0']);
    }

    public function testItThrowAnExceptionWhenTryingToRenderAnInvalidLicense(): void
    {
        $this->expectException(NotFoundException::class);

        $this->factory->shouldReceive('getLicenseAgreementById')->with($this->project, 1)->andReturnNull();

        $this->controller->process($this->request, $this->layout, ['project_id' => '101', 'id' => '1']);
    }

    public function testItThrowsAndExceptionWhenServiceIsNotAvailable(): void
    {
        $this->project->shouldReceive('getFileService')->andReturnNull();

        $this->expectException(NotFoundException::class);

        $this->controller->process($this->request, $this->layout, ['project_id' => '101', 'id' => '1']);
    }

    public function testItThrowsAnExceptionWhenUserIsNotFileAdministrator(): void
    {
        $this->permissions_manager->shouldReceive('isAdmin')->with($this->project, $this->current_user)->andReturnFalse();

        $this->expectException(ForbiddenException::class);

        $this->controller->process($this->request, $this->layout, ['project_id' => '101', 'id' => '1']);
    }
}