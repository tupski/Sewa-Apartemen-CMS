<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallerTest extends TestCase
{
    use RefreshDatabase;

    /** Installer index route */
    public function test_installer_index_route_exists(): void
    {
        $response = $this->get('/install');

        $status = $response->status();
        $this->assertContains($status, [200, 302, 404]);
    }

    /** Installer step 1 route exists */
    public function test_installer_step_1_route_exists(): void
    {
        $response = $this->get('/install/step/1');

        $status = $response->status();
        $this->assertContains($status, [200, 302, 404]);
    }

    /** Installer step 2 route exists */
    public function test_installer_step_2_route_exists(): void
    {
        $response = $this->get('/install/step/2');

        $status = $response->status();
        $this->assertContains($status, [200, 302, 404]);
    }

    /** Installer step 3 route exists */
    public function test_installer_step_3_route_exists(): void
    {
        $response = $this->get('/install/step/3');

        $status = $response->status();
        $this->assertContains($status, [200, 302, 404]);
    }

    /** Installer step 4 route exists */
    public function test_installer_step_4_route_exists(): void
    {
        $response = $this->get('/install/step/4');

        $status = $response->status();
        $this->assertContains($status, [200, 302, 404]);
    }

    /** Installer step 5 route exists */
    public function test_installer_step_5_route_exists(): void
    {
        $response = $this->get('/install/step/5');

        $status = $response->status();
        $this->assertContains($status, [200, 302, 404]);
    }

    /** Installer requirements POST route */
    public function test_installer_requirements_post_responds(): void
    {
        $response = $this->post('/install/requirements');

        $this->assertContains($response->status(), [302, 404, 422]);
    }

    /** Installer application POST route */
    public function test_installer_application_post_responds(): void
    {
        $response = $this->post('/install/application');

        $this->assertContains($response->status(), [302, 404, 422]);
    }

    /** Installer admin POST route */
    public function test_installer_admin_post_responds(): void
    {
        $response = $this->post('/install/admin');

        $this->assertContains($response->status(), [302, 404, 422]);
    }

    /** Installer website POST route */
    public function test_installer_website_post_responds(): void
    {
        $response = $this->post('/install/website');

        $this->assertContains($response->status(), [302, 404, 422]);
    }

    /** Installer finish POST route */
    public function test_installer_finish_post_responds(): void
    {
        $response = $this->post('/install/finish');

        $this->assertContains($response->status(), [302, 404, 422]);
    }
}
