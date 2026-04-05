<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Form\Extension;

use Sylius\Bundle\CoreBundle\Form\Type\Checkout\AddressType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Adds a newsletter subscription checkbox to the checkout address form.
 */
final class CheckoutNewsletterExtension extends AbstractTypeExtension
{
    public function __construct(
        private readonly bool $enabled,
    ) {
    }

    public static function getExtendedTypes(): iterable
    {
        return [AddressType::class];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!$this->enabled) {
            return;
        }

        $builder->add('subscribedToNewsletter', CheckboxType::class, [
            'required' => false,
            'mapped' => false,
            'label' => 'renrhaf_sylius_brevo.ui.subscribe_newsletter',
            'attr' => ['class' => 'form-check-input'],
        ]);
    }
}
