<?php

namespace Tests\Feature;

use Illuminate\Support\Arr;
use Tests\TestCase;

/**
 * The shop speaks German. The framework's own messages are translated in
 * lang/de; this guards against a message silently staying English after a
 * framework update adds new ones.
 */
class LanguageFilesTest extends TestCase
{
    public function test_the_application_locale_is_german(): void
    {
        $this->assertSame('de', app()->getLocale());
    }

    public function test_every_framework_message_has_a_german_counterpart(): void
    {
        $source = base_path('vendor/laravel/framework/src/Illuminate/Translation/lang/en');

        foreach (['auth', 'pagination', 'passwords', 'validation'] as $group) {
            $english = Arr::except(require "{$source}/{$group}.php", ['custom', 'attributes']);
            $german = require lang_path("de/{$group}.php");

            $missing = array_diff(array_keys(Arr::dot($english)), array_keys(Arr::dot($german)));

            $this->assertSame([], array_values($missing), "lang/de/{$group}.php lacks messages.");
        }
    }

    public function test_placeholders_survive_the_translation(): void
    {
        $source = base_path('vendor/laravel/framework/src/Illuminate/Translation/lang/en');
        $english = Arr::dot(Arr::except(require "{$source}/validation.php", ['custom', 'attributes']));
        $german = Arr::dot(require lang_path('de/validation.php'));

        foreach ($english as $key => $message) {
            preg_match_all('/:(?!attribute\b)[a-z_]+/', $message, $expected);
            preg_match_all('/:(?!attribute\b)[a-z_]+/', $german[$key], $actual);

            $this->assertSame(
                [],
                array_values(array_diff(array_unique($expected[0]), $actual[0])),
                "validation.{$key} lost a placeholder.",
            );
        }
    }

    public function test_a_validation_message_reads_german(): void
    {
        $validator = validator(['email' => ''], ['email' => 'required']);

        $this->assertSame('Bitte füllen Sie das Feld E-Mail-Adresse aus.', $validator->errors()->first('email'));
    }
}
