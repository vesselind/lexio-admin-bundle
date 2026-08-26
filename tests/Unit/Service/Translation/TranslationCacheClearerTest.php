<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Service\Translation;

use Lexio\AdminBundle\Service\Translation\TranslationCacheClearer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class TranslationCacheClearerTest extends TestCase
{
    private string $cacheDirectory;

    protected function setUp(): void
    {
        $this->cacheDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lexio-cache-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($this->cacheDirectory . DIRECTORY_SEPARATOR . 'translations', 0777, true));
        self::assertNotFalse(file_put_contents($this->cacheDirectory . DIRECTORY_SEPARATOR . 'translations' . DIRECTORY_SEPARATOR . 'catalog.php', 'cached'));
        self::assertNotFalse(file_put_contents($this->cacheDirectory . DIRECTORY_SEPARATOR . 'container.php', 'keep'));
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->cacheDirectory);
    }

    public function test_it_removes_only_the_translation_cache_directory(): void
    {
        (new TranslationCacheClearer(new Filesystem(), $this->cacheDirectory))->clear();

        self::assertDirectoryDoesNotExist($this->cacheDirectory . DIRECTORY_SEPARATOR . 'translations');
        self::assertFileExists($this->cacheDirectory . DIRECTORY_SEPARATOR . 'container.php');
    }

    public function test_it_is_a_no_op_when_the_translation_cache_does_not_exist(): void
    {
        (new Filesystem())->remove($this->cacheDirectory . DIRECTORY_SEPARATOR . 'translations');

        (new TranslationCacheClearer(new Filesystem(), $this->cacheDirectory))->clear();

        self::assertFileExists($this->cacheDirectory . DIRECTORY_SEPARATOR . 'container.php');
    }
}
