<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Menu;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: 'sylius.menu.admin.main')]
final class AdminMenuListener
{
    public function __invoke(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        $configMenu = $menu->getChild('configuration');

        if (null === $configMenu) {
            return;
        }

        $configMenu
            ->addChild('brevo_dashboard', ['route' => 'renrhaf_sylius_brevo_admin_dashboard'])
            ->setLabel('renrhaf_sylius_brevo.ui.brevo')
            ->setLabelAttribute('icon', 'tabler:mail-bolt');
    }
}
