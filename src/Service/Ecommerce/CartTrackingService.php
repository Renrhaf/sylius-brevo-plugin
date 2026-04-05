<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Service\Ecommerce;

use Psr\Log\LoggerInterface;
use Renrhaf\SyliusBrevoPlugin\Api\BrevoClientInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;

/**
 * Tracks cart events for Brevo abandoned cart automations.
 * Events: cart_updated, order_completed, cart_deleted.
 */
final class CartTrackingService implements CartTrackingServiceInterface
{
    public function __construct(
        private readonly BrevoClientInterface $brevoClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function trackCartUpdated(OrderInterface $cart): void
    {
        $email = $this->getCustomerEmail($cart);

        if (null === $email) {
            return;
        }

        $items = [];

        /** @var OrderItemInterface $item */
        foreach ($cart->getItems() as $item) {
            $variant = $item->getVariant();
            $product = $variant?->getProduct();
            $image = $product?->getImages()->first();

            $items[] = [
                'name' => $item->getProductName() ?? '',
                'price' => $item->getUnitPrice() / 100,
                'quantity' => $item->getQuantity(),
                'url' => '',
                'image' => (false !== $image) ? ($image->getPath() ?? '') : '',
            ];
        }

        $this->brevoClient->trackEvent('cart_updated', $email, [
            'id' => 'cart-' . $cart->getId(),
            'data' => [
                'total' => $cart->getTotal() / 100,
                'currency' => $cart->getCurrencyCode(),
                'items' => $items,
            ],
        ]);

        $this->logger->debug('Cart updated event sent to Brevo', ['cart_id' => $cart->getId()]);
    }

    public function trackCartDeleted(OrderInterface $cart): void
    {
        $email = $this->getCustomerEmail($cart);

        if (null === $email) {
            return;
        }

        $this->brevoClient->trackEvent('cart_deleted', $email, [
            'id' => 'cart-' . $cart->getId(),
        ]);

        $this->logger->debug('Cart deleted event sent to Brevo', ['cart_id' => $cart->getId()]);
    }

    public function trackOrderCompleted(OrderInterface $order): void
    {
        $email = $this->getCustomerEmail($order);

        if (null === $email) {
            return;
        }

        $this->brevoClient->trackEvent('order_completed', $email, [
            'id' => 'cart-' . $order->getId(),
            'data' => [
                'order_id' => $order->getNumber(),
                'total' => $order->getTotal() / 100,
                'currency' => $order->getCurrencyCode(),
            ],
        ]);

        $this->logger->debug('Order completed event sent to Brevo', ['order' => $order->getNumber()]);
    }

    private function getCustomerEmail(OrderInterface $order): ?string
    {
        $customer = $order->getCustomer();

        if (null === $customer) {
            return null;
        }

        $email = $customer->getEmail();

        return (null !== $email && '' !== $email) ? $email : null;
    }
}
