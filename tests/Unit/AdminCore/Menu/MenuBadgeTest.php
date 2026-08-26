<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\AdminCore\Menu;

use Lexio\AdminBundle\AdminCore\Menu\Link;
use Lexio\AdminBundle\AdminCore\Menu\MenuBadge;
use Lexio\AdminBundle\AdminCore\Menu\MenuBuilder;
use Lexio\AdminBundle\AdminCore\Menu\Separator;
use Lexio\AdminBundle\AdminCore\Menu\SubMenu;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;

final class MenuBadgeTest extends TestCase
{
    public function test_static_badge_metadata_is_available_on_a_link(): void
    {
        $badge = new MenuBadge('New', 'mdi:star', 'success');
        $builder = new MenuBuilder(new RequestStack());

        $builder->addLink('Messages', '/messages', 'mdi:email', $badge);
        $link = $builder->getMenuItems()->first();

        self::assertInstanceOf(Link::class, $link);
        self::assertSame($badge, $link->getBadge());
        self::assertSame('New', $link->getBadge()?->getCaption());
        self::assertSame('mdi:star', $link->getBadge()?->getIcon());
        self::assertSame('success', $link->getBadge()?->getColor());
    }

    public function test_callback_is_evaluated_when_badge_value_is_read(): void
    {
        $calls = 0;
        $badge = new MenuBadge(
            callback: static function () use (&$calls): int {
                ++$calls;

                return 7;
            },
        );
        $builder = new MenuBuilder(new RequestStack());
        $builder->addLink('Messages', '/messages', badge: $badge);
        $link = $builder->getMenuItems()->first();

        self::assertInstanceOf(Link::class, $link);
        self::assertSame(0, $calls);
        self::assertSame(7, $link->getBadge()?->getCaption());
        self::assertSame(1, $calls);
    }

    public function test_callback_can_hide_a_badge(): void
    {
        $badge = new MenuBadge(callback: static fn (): ?string => null);
        $builder = new MenuBuilder(new RequestStack());
        $builder->addLink('Messages', '/messages', badge: $badge);
        $link = $builder->getMenuItems()->first();

        self::assertInstanceOf(Link::class, $link);
        self::assertNull($link->getBadge()?->getCaption());
    }

    public function test_empty_callback_value_is_normalized_for_template_suppression(): void
    {
        $badge = new MenuBadge(callback: static fn (): string => '');
        $template = file_get_contents(__DIR__ . '/../../../../templates/components/Admin/SideMenu.html.twig');

        self::assertNull($badge->getCaption());
        self::assertIsString($template);
        self::assertStringContainsString('badgeCaption is not null', $template);
    }

    public function test_badges_are_supported_on_submenu_headers_and_links(): void
    {
        $headerBadge = new MenuBadge(2, color: 'warning');
        $linkBadge = new MenuBadge('New');
        $builder = new MenuBuilder(new RequestStack());

        $builder
            ->addSubMenu('Messages', badge: $headerBadge)
            ->addLink('Inbox', '/messages', badge: $linkBadge);

        $subMenu = $builder->getMenuItems()->first();

        self::assertInstanceOf(SubMenu::class, $subMenu);
        self::assertSame(2, $subMenu->getBadge()?->getCaption());
        self::assertSame($linkBadge, $subMenu->getLinks()->first()->getBadge());
    }

    public function test_separator_satisfies_the_menu_badge_contract_without_a_badge(): void
    {
        self::assertNull((new Separator('System'))->getBadge());
    }

    public function test_template_renders_static_and_dynamic_badge_properties(): void
    {
        $template = file_get_contents(__DIR__ . '/../../../../templates/components/Admin/SideMenu.html.twig');

        self::assertIsString($template);
        self::assertStringContainsString('badge.caption', $template);
        self::assertStringContainsString('badge.icon', $template);
        self::assertStringContainsString('badge.color', $template);
        self::assertStringContainsString('linkBadgeCaption', $template);
    }
}
