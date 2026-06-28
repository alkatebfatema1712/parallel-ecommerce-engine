<?php

namespace Tests\Feature;

use Tests\TestCase;

class ParallelProjectStructureTest extends TestCase
{
    public function test_parallel_project_files_exist(): void
    {
        $this->assertFileExists(app_path('Services/CheckoutService.php'));
        $this->assertFileExists(app_path('Services/ResourceSemaphore.php'));
        $this->assertFileExists(app_path('Http/Middleware/PerformanceMonitor.php'));
        $this->assertFileExists(base_path('PROJECT_DELIVERY/jmeter/DreamStore_Parallel_100Users.jmx'));
    }
}
