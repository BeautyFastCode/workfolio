<?php

declare(strict_types=1);

namespace App\Contact\SubmitContactMessage\UI;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<ContactMessageData>
 */
final class ContactMessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', TextType::class, [
                'label' => 'contact_message.full_name',
                'attr' => [
                    'placeholder' => 'contact_message.full_name_placeholder',
                    'autocomplete' => 'name',
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'contact_message.email',
                'attr' => [
                    'placeholder' => 'contact_message.email_placeholder',
                    'autocomplete' => 'email',
                ],
            ])
            ->add('subject', TextType::class, [
                'label' => 'contact_message.subject',
                'attr' => [
                    'placeholder' => 'contact_message.subject_placeholder',
                ],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'contact_message.message',
                'attr' => [
                    'placeholder' => 'contact_message.message_placeholder',
                    'rows' => 8,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactMessageData::class,
        ]);
    }
}
