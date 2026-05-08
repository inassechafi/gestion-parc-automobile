<?php

namespace App\Form;

use App\Entity\Vehicle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class VehicleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('immatriculation', TextType::class, [
                'attr' => [
                    'placeholder' => 'AA-123-BB',
                    'maxlength' => 50,
                ],
            ])
            ->add('modele', TextType::class, [
                'attr' => [
                    'placeholder' => 'Peugeot 308, Renault Clio...',
                ],
            ])
            ->add('etat', ChoiceType::class, [
                'choices' => [
                    'Disponible' => 'disponible',
                    'En service' => 'en service',
                    'En panne' => 'en panne',
                    'En entretien' => 'en entretien',
                    'Hors service' => 'hors service',
                ],
                'placeholder' => 'Sélectionnez un état',
            ])
            ->add('kilometrage', IntegerType::class, [
                'attr' => [
                    'min' => 0,
                    'step' => 1,
                    'placeholder' => '0',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vehicle::class,
        ]);
    }
}