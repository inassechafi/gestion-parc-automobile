<?php

namespace App\Form;

use App\Entity\Affectation;
use App\Entity\User;
use App\Entity\Vehicle;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Validator\Constraints\NotNull;

class AffectationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateDebut', DateTimeType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'label' => 'Date de début',
                'constraints' => [
                    new NotNull([
                        'message' => 'La date de début est obligatoire',
                    ]),
                ],
            ])
            ->add('dateFin', DateTimeType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'required' => false,
                'label' => 'Date de fin (optionnelle)',
            ])
            ->add('vehicle', EntityType::class, [
                'class' => Vehicle::class,
                'choice_label' => 'immatriculation',
                'label' => 'Véhicule',
                'placeholder' => 'Sélectionnez un véhicule',
            ])
            ->add('conducteur', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user) {
                    return $user->getPrenom() . ' ' . $user->getNom();
                },
                'label' => 'Conducteur',
                'placeholder' => 'Sélectionnez un conducteur',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Affectation::class,
        ]);
    }
}