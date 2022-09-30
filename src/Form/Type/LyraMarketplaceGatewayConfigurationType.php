<?php

namespace Akki\SyliusPayumLyraMarketplacePlugin\Form\Type;

use Akki\SyliusPayumLyraMarketplacePlugin\Api\Api;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

final class LyraMarketplaceGatewayConfigurationType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'akki.lyra_marketplace.fields.username.label',
                'help' => 'akki.lyra_marketplace.fields.username.help',
                'constraints' => [
                    new NotBlank([
                        'message' => 'akki.lyra_marketplace.username.not_blank',
                        'groups' => ['sylius']
                    ]),
                ],
            ])
            ->add('password', TextType::class, [
                'label' => 'akki.lyra_marketplace.fields.password.label',
                'constraints' => [
                    new NotBlank([
                        'message' => 'akki.lyra_marketplace.password.not_blank',
                        'groups' => ['sylius']
                    ]),
                ],
            ])
            ->add('ctx_mode', ChoiceType::class, [
                'label' => 'akki.lyra_marketplace.fields.ctx_mode.label',
                'choices' => [
                    'akki.lyra_marketplace.ctx_mode.production' => Api::MODE_PRODUCTION,
                    'akki.lyra_marketplace.ctx_mode.test' => Api::MODE_TEST
                ],
            ])
        ;
    }
}
