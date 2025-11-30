<?php

namespace App\Form;

use App\Entity\Avis;
use App\Entity\Reservation;
use App\Repository\OuvrierRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AvisType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $reservations = $options['reservations'] ?? [];

        $builder
            ->add('reservation', EntityType::class, [
                'class' => Reservation::class,
                'choices' => $reservations,
                'choice_label' => function (Reservation $reservation) {
                    $ouvrier = null;
                    if ($reservation->getOuvrierId()) {
                        // Get ouvrier name if possible
                        $ouvrierInfo = 'Ouvrier #' . $reservation->getOuvrierId();
                    } else {
                        $ouvrierInfo = 'Aucun ouvrier';
                    }
                    return sprintf(
                        'Réservation #%d - %s (%s) - %s',
                        $reservation->getId(),
                        $reservation->getDate() ? $reservation->getDate()->format('d/m/Y') : 'N/A',
                        $reservation->getTypeService(),
                        $ouvrierInfo
                    );
                },
                'label' => 'Réservation',
                'placeholder' => 'Sélectionnez une réservation',
                'attr' => ['class' => 'form-select'],
                'required' => true,
            ])
            ->add('note', ChoiceType::class, [
                'label' => 'Note',
                'choices' => [
                    '1 étoile' => 1,
                    '2 étoiles' => 2,
                    '3 étoiles' => 3,
                    '4 étoiles' => 4,
                    '5 étoiles' => 5,
                ],
                'attr' => ['class' => 'form-select'],
                'required' => true,
            ])
            ->add('commentaire', TextareaType::class, [
                'label' => 'Commentaire',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Laissez votre commentaire sur le service...'
                ],
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Avis::class,
            'reservations' => [],
        ]);
    }
}
