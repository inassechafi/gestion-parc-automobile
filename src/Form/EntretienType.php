<?php

namespace App\Form;

use App\Entity\Entretien;
use App\Entity\Vehicle;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;


class EntretienType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DateTimeType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'label' => 'Date et heure',
                'required' => true,
                'attr' => [
                    'class' => 'form-control'
                ],
            ])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Révision' => 'Révision',
                    'Réparation' => 'Réparation',
                    'Entretien' => 'Entretien',
                    'Contrôle technique' => 'Contrôle technique',
                    'Vidange' => 'Vidange',
                    'Autre' => 'Autre',
                ],
                'placeholder' => 'Sélectionnez un type',
                'label' => 'Type d\'entretien',
                'required' => true,
                'attr' => [
                    'class' => 'form-select'
                ],
            ])
            ->add('cout', NumberType::class, [
                'scale' => 2,
                'attr' => [
                    'step' => '0.01',
                    'min' => '0',
                    'placeholder' => '0.00',
                    'class' => 'form-control'
                ],
                'label' => 'Coût (DH)',
                'html5' => true,
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Description détaillée des travaux réalisés...',
                    'class' => 'form-control'
                ],
                'label' => 'Description (optionnelle)',
            ])
            ->add('vehicle', EntityType::class, [
                'class' => Vehicle::class,
                'choice_label' => function(Vehicle $vehicle) {
                    return $vehicle->getImmatriculation() . ' - ' . $vehicle->getModele();
                },
                'placeholder' => 'Sélectionnez un véhicule',
                'label' => 'Véhicule concerné',
                'required' => true,
                'attr' => [
                    'class' => 'form-select'
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Entretien::class,
        ]);
    }
}