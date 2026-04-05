<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Service\Newsletter;

interface NewsletterServiceInterface
{
    public function subscribe(string $email, string $firstName = '', string $lastName = ''): void;

    public function unsubscribe(string $email): void;

    public function isSubscribed(string $email): bool;
}
