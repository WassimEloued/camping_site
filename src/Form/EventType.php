<?php

namespace App\Form;

use App\Entity\Event;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\{
    TextType,
    TextareaType,
    NumberType,
    DateTimeType,
    FileType,
    SubmitType
};
use Symfony\Component\Validator\Constraints\{
    NotBlank,
    Length,
    Positive,
    Image,
    GreaterThan
};

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 3, 'max' => 255]),
                ],
                'attr' => ['class' => 'form-control mb-3']
            ])
            ->add('description', TextareaType::class, [
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 10]),
                ],
                'attr' => ['class' => 'form-control mb-3', 'rows' => 5]
            ])
            ->add('location', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 3, 'max' => 255]),
                ],
                'attr' => ['class' => 'form-control mb-3']
            ])
            ->add('budget', NumberType::class, [
                'scale' => 2,
                'constraints' => [
                    new NotBlank(),
                    new Positive(),
                ],
                'attr' => ['class' => 'form-control mb-3']
            ])
            ->add('maxParticipants', NumberType::class, [
                'constraints' => [
                    new NotBlank(),
                    new Positive(),
                ],
                'attr' => ['class' => 'form-control mb-3']
            ])
            ->add('startDate', DateTimeType::class, [
                'widget' => 'single_text',
                'constraints' => [
                    new NotBlank(),
                    new GreaterThan('today'),
                ],
                'attr' => ['class' => 'form-control mb-3']
            ])
            ->add('endDate', DateTimeType::class, [
                'widget' => 'single_text',
                'constraints' => [
                    new NotBlank(),
                    new GreaterThan([
                        'propertyPath' => 'parent.all[startDate].data'
                    ]),
                ],
                'attr' => ['class' => 'form-control mb-3']
            ])
            ->add('photo', FileType::class, [
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Image([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png'],
                    ])
                ],
                'attr' => ['class' => 'form-control mb-3']
            ])
            ->add('submit', SubmitType::class, [
                'attr' => ['class' => 'btn btn-primary mt-3']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'event_form',
        ]);
    }
}