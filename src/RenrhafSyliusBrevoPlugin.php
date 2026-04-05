<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin;

use Renrhaf\SyliusBrevoPlugin\DependencyInjection\RenrhafSyliusBrevoExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class RenrhafSyliusBrevoPlugin extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function getContainerExtension(): ExtensionInterface
    {
        return new RenrhafSyliusBrevoExtension();
    }
}
