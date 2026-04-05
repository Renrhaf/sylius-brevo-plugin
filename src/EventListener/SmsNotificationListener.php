<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\EventListener;

use Renrhaf\SyliusBrevoPlugin\Service\Sms\SmsServiceInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Sends SMS notifications via Brevo on order events.
 */
final class SmsNotificationListener
{
    public function __construct(
        private readonly SmsServiceInterface $smsService,
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

        $phone = $this->getPhoneNumber($order);

        if (null === $phone) {
            return;
        }

        $this->smsService->sendOrderConfirmation(
            $phone,
            $order->getNumber() ?? '',
            number_format($order->getTotal() / 100, 2, '.', '') . ' ' . ($order->getCurrencyCode() ?? 'EUR'),
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

        $phone = $this->getPhoneNumber($order);

        if (null === $phone) {
            return;
        }

        $this->smsService->sendOrderShipped($phone, $order->getNumber() ?? '');
    }

    private function getPhoneNumber(OrderInterface $order): ?string
    {
        // Try billing address first, then customer
        $billingAddress = $order->getBillingAddress();

        if (null !== $billingAddress) {
            $phone = $billingAddress->getPhoneNumber();

            if (null !== $phone && '' !== $phone) {
                return $phone;
            }
        }

        $customer = $order->getCustomer();

        if ($customer instanceof CustomerInterface) {
            $phone = $customer->getPhoneNumber();

            if (null !== $phone && '' !== $phone) {
                return $phone;
            }
        }

        return null;
    }
}
