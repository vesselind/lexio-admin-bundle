<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Controller;

use Lexio\AdminBundle\Controller\Admin\MenuController;
use Lexio\AdminBundle\Controller\Admin\PageController;
use Lexio\AdminBundle\Controller\Admin\SecurityLogController;
use Lexio\AdminBundle\Contract\Page\PageAdministrationInterface;
use Lexio\AdminBundle\Filter\BaseFilter;
use Lexio\AdminBundle\Filter\SecurityLogFilter;
use Lexio\AdminBundle\Page\PageManager;
use PHPUnit\Framework\TestCase;

final class AdminControllerOwnershipTest extends TestCase
{
    public function test_bundle_owns_the_reusable_admin_controller_boundaries(): void
    {
        foreach ([MenuController::class, PageController::class, SecurityLogController::class] as $controller) {
            self::assertTrue(class_exists($controller));
            self::assertTrue((new \ReflectionClass($controller))->isAbstract());
        }
    }

    public function test_security_log_filter_is_bundle_owned(): void
    {
        self::assertTrue(class_exists(SecurityLogFilter::class));
        self::assertSame(BaseFilter::class, get_parent_class(SecurityLogFilter::class));
    }

    public function test_page_administration_uses_a_public_bundle_contract(): void
    {
        self::assertTrue(is_a(PageManager::class, PageAdministrationInterface::class, true));
    }
}
