<?php

namespace Tests\Unit;

use App\Services\TranslationIntegrityValidator;
use PHPUnit\Framework\TestCase;

class TranslationIntegrityValidatorTest extends TestCase
{
    public function test_extra_keyboard_mnemonic_is_rejected(): void
    {
        $issues = (new TranslationIntegrityValidator())->validate(
            'Manage Profiles & Connect',
            'Gerenciar perfis e &conectar'
        );

        $this->assertNotEmpty($issues);
        $this->assertSame('portal.validation_accelerators', $issues[0]['key']);
    }

    public function test_raw_tab_shortcut_is_preserved(): void
    {
        $issues = (new TranslationIntegrityValidator())->validate(
            "&Open\tCtrl+O",
            "&Abrir\tCtrl+O"
        );

        $this->assertSame([], $issues);
    }
}
