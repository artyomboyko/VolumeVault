<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PrimaryButtonStyleTest extends TestCase
{
    public function test_btn_primary_uses_the_global_outlined_sky_style(): void
    {
        $css = file_get_contents(dirname(__DIR__, 2).'/resources/css/app.css');

        $this->assertNotFalse($css);
        $this->assertStringContainsString('.btn-primary', $css);
        $this->assertStringContainsString('border-sky-300/30', $css);
        $this->assertStringContainsString('bg-sky-400/10', $css);
        $this->assertStringContainsString('dark:focus:ring-sky-400/30', $css);
        $this->assertStringNotContainsString('.btn-toolbar-primary', $css);
    }

    public function test_toolbar_pages_use_btn_primary_instead_of_a_special_case_class(): void
    {
        foreach ([
            'BackupJobs/Index.vue',
            'Volumes/Index.vue',
            'Stacks/Index.vue',
            'Alerts/Index.vue',
        ] as $page) {
            $contents = file_get_contents(dirname(__DIR__, 2).'/resources/js/Pages/'.$page);

            $this->assertNotFalse($contents);
            $this->assertStringContainsString('btn-primary', $contents, $page);
            $this->assertStringNotContainsString('btn-toolbar-primary', $contents, $page);
        }
    }
}
