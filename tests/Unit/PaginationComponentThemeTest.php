<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PaginationComponentThemeTest extends TestCase
{
    public function test_pagination_select_uses_dark_theme_control_styling(): void
    {
        $component = file_get_contents(dirname(__DIR__, 2).'/resources/js/Components/Pagination.vue');

        $this->assertNotFalse($component);
        $this->assertMatchesRegularExpression('/<select[^>]+class="[^"]*input[^"]*dark:\[color-scheme:dark\][^"]*"/s', $component);
        $this->assertMatchesRegularExpression('/<option[^>]+class="[^"]*dark:bg-slate-950[^"]*dark:text-slate-100[^"]*"/s', $component);
    }
}
