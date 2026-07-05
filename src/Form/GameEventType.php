<?php

namespace App\Form;

use App\Entity\GameEvent;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GameEventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('timecode', TextType::class, [
                'label' => 'Minute',
                'attr' => ['placeholder' => "67'"],
            ])
            ->add('message', TextType::class, [
                'label' => 'Ereignis',
                'attr' => ['placeholder' => 'Was ist passiert?'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GameEvent::class,
        ]);
    }
}
