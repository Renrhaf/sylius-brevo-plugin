<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\EventListener;

use Renrhaf\SyliusBrevoPlugin\Service\Email\TransactionalEmailServiceInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Sends Brevo template-based transactional emails on Sylius events.
 */
final class TransactionalEmailListener
{
    public function __construct(
        private readonly TransactionalEmailServiceInterface $emailService,
        private readonly bool $enabled,
    ) {
    }

    #[AsEventListener(event: 'sylius.order.post_complete')]
    public function onOrderComplete(GenericEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $order = $event->getSubject();

        if (!$order instanceof OrderInterface) {
            return;
        }

        $customer = $order->getCustomer();

        if (!$customer instanceof CustomerInterface || null === $customer->getEmail()) {
            return;
        }

        $this->emailService->sendOrderConfirmation(
            $customer->getEmail(),
            $customer->getFullName(),
            $this->buildOrderParams($order),
        );
    }

    #[AsEventListener(event: 'sylius.shipment.post_ship')]
    public function onOrderShipped(GenericEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $shipment = $event->getSubject();

        if (!method_exists($shipment, 'getOrder')) {
            return;
        }

        $order = $shipment->getOrder();

        if (!$order instanceof OrderInterface) {
            return;
        }

        $customer = $order->getCustomer();

        if (!$customer instanceof CustomerInterface || null === $customer->getEmail()) {
            return;
        }

        $this->emailService->sendOrderShipped(
            $customer->getEmail(),
            $customer->getFullName(),
            $this->buildOrderParams($order),
        );
    }

    #[AsEventListener(event: 'sylius.customer.post_create')]
    public function onCustomerCreate(GenericEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $customer = $event->getSubject();

        if (!$customer instanceof CustomerInterface || null === $customer->getEmail()) {
            return;
        }

        $this->emailService->sendCustomerWelcome(
            $customer->getEmail(),
            $customer->getFullName(),
            [
                'FIRSTNAME' => $customer->getFirstName() ?? '',
                'LASTNAME' => $customer->getLastName() ?? '',
            ],
        );
    }

    /** @return array<string, mixed> */
    private function buildOrderParams(OrderInterface $order): array
    {
        $items = [];

        foreach ($order->getItems() as $item) {
            $items[] = [
                'name' => $item->getProductName(),
                'quantity' => $item->getQuantity(),
                'price' => number_format($item->getUnitPrice() / 100, 2, '.', ''),
            ];
        }

        return [
            'ORDER_NUMBER' => $order->getNumber(),
            'ORDER_TOTAL' => number_format($order->getTotal() / 100, 2, '.', ''),
            'ORDER_DATE' => $order->getCreatedAt()?->format('d/m/Y') ?? '',
            'ORDER_ITEMS' => $items,
            'CURRENCY' => $order->getCurrencyCode(),
        ];
    }
}
