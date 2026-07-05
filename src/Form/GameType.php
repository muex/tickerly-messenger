<?php

namespace App\Form;

use App\Entity\Game;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GameType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('home', TextType::class, [
                'label' => 'Heimmannschaft',
                'attr' => ['placeholder' => 'z. B. Falcons'],
            ])
            ->add('away', TextType::class, [
                'label' => 'Gastmannschaft',
                'attr' => ['placeholder' => 'z. B. Sharks'],
            ])
            ->add('location', TextType::class, [
                'label' => 'Austragungsort',
                'attr' => ['placeholder' => 'z. B. Stadthalle'],
            ])
            ->add('datetime', DateTimeType::class, [
                'label' => 'Datum & Uhrzeit',
                'widget' => 'single_text',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Game::class,
        ]);
    }
}
