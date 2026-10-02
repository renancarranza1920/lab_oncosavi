<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_panel_requires_authentication(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/admin');
        $this->get('/admin')->assertRedirect(route('filament.admin.auth.login'));
    }
}
