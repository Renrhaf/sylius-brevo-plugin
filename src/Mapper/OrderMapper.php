<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Mapper;

use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;

final class OrderMapper implements OrderMapperInterface
{
    public function map(OrderInterface $order): array
    {
        $customer = $order->getCustomer();

        if (null === $customer) {
            throw new \InvalidArgumentException('Cannot map order without customer.');
        }

        $products = [];

        /** @var OrderItemInterface $item */
        foreach ($order->getItems() as $item) {
            $variant = $item->getVariant();
            $products[] = [
                'productId' => $variant?->getProduct()?->getCode() ?? $item->getProductName(),
                'price' => $item->getUnitPrice() / 100,
                'quantity' => $item->getQuantity(),
            ];
        }

        // Map Sylius state to Brevo-friendly status
        $status = match ($order->getState()) {
            OrderInterface::STATE_NEW => 'pending',
            OrderInterface::STATE_FULFILLED => 'completed',
            OrderInterface::STATE_CANCELLED => 'cancelled',
            default => $order->getState(),
        };

        $payload = [
            'email' => $customer->getEmail(),
            'id' => $order->getNumber(),
            'createdAt' => $order->getCreatedAt()?->format('Y-m-d\TH:i:s\Z') ?? date('Y-m-d\TH:i:s\Z'),
            'updatedAt' => $order->getUpdatedAt()?->format('Y-m-d\TH:i:s\Z') ?? date('Y-m-d\TH:i:s\Z'),
            'status' => $status,
            'amount' => $order->getTotal() / 100,
            'products' => $products,
        ];

        // Billing info
        $billingAddress = $order->getBillingAddress();

        if (null !== $billingAddress) {
            $payload['billing'] = [
                'address' => $billingAddress->getStreet(),
                'city' => $billingAddress->getCity(),
                'countryCode' => $billingAddress->getCountryCode(),
                'phone' => $billingAddress->getPhoneNumber(),
                'postCode' => $billingAddress->getPostcode(),
                'paymentMethod' => $order->getPayments()->first()?->getMethod()?->getName() ?? 'unknown',
            ];
        }

        // Coupons — extract from promotions applied to the order
        $coupons = [];
        $promotionCoupon = $order->getPromotionCoupon();

        if (null !== $promotionCoupon) {
            $coupons[] = $promotionCoupon->getCode();
        }

        if ([] !== $coupons) {
            $payload['coupons'] = $coupons;
        }

        return $payload;
    }
}
