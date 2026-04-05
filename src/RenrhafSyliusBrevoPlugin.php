<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin;

use Symfony\Component\HttpKernel\Bundle\Bundle;

final class RenrhafSyliusBrevoPlugin extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
