<?php

namespace App\Controller\Admin;

use App\Entity\Ouvrier;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;

class OuvrierCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Ouvrier::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            IdField::new('id')->onlyOnIndex(),
        ];

        if ($pageName === Crud::PAGE_INDEX) {
            $fields[] = TextField::new('nom', 'Nom');
            $fields[] = AssociationField::new('user', 'User')
                ->formatValue(function ($value, $entity) {
                    if ($entity && $entity->getUser()) {
                        $user = $entity->getUser();
                        $client = $user->getClient();
                        $name = $client ? $client->getNom() : 'N/A';
                        return sprintf('%s (ID: %d) - %s', $name, $user->getId(), $user->getEmail());
                    }
                    return 'N/A';
                });
            $fields[] = TextField::new('zoneService', 'Zone de Service');
            $fields[] = BooleanField::new('disponible', 'Disponible');
        } else {
            $fields[] = AssociationField::new('user', 'User Account')
                ->setRequired(true)
                ->autocomplete()
                ->formatValue(function ($value, $entity) {
                    if ($entity && $entity->getUser()) {
                        $user = $entity->getUser();
                        $client = $user->getClient();
                        $name = $client ? $client->getNom() : 'N/A';
                        return sprintf('ID: %d | Name: %s | Email: %s', $user->getId(), $name, $user->getEmail());
                    }
                    return 'N/A';
                })
                ->setHelp('Select an existing user account. The user\'s role will be automatically changed to ROLE_OVR when saved.');
            $fields[] = TextField::new('nom', 'Nom')
                ->setRequired(true)
                ->setHelp('Name of the ouvrier');
            $fields[] = TextField::new('zoneService', 'Zone de Service')
                ->setRequired(true)
                ->setHelp('Service area/zone');
            $fields[] = BooleanField::new('disponible', 'Disponible')
                ->setRequired(true)
                ->setHelp('Whether the ouvrier is available');
        }

        return $fields;
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var Ouvrier $ouvrier */
        $ouvrier = $entityInstance;
        
        if ($ouvrier->getUser()) {
            $user = $ouvrier->getUser();
            $roles = $user->getRoles();
            
            // Remove ROLE_USER if present and add ROLE_OVR
            $roles = array_filter($roles, fn($role) => $role !== 'ROLE_USER');
            if (!in_array('ROLE_OVR', $roles)) {
                $roles[] = 'ROLE_OVR';
            }
            
            $user->setRoles(array_values($roles));
            $entityManager->persist($user);
        }
        
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var Ouvrier $ouvrier */
        $ouvrier = $entityInstance;
        
        if ($ouvrier->getUser()) {
            $user = $ouvrier->getUser();
            $roles = $user->getRoles();
            
            // Remove ROLE_USER if present and ensure ROLE_OVR is present
            $roles = array_filter($roles, fn($role) => $role !== 'ROLE_USER');
            if (!in_array('ROLE_OVR', $roles)) {
                $roles[] = 'ROLE_OVR';
            }
            
            $user->setRoles(array_values($roles));
            $entityManager->persist($user);
        }
        
        parent::updateEntity($entityManager, $entityInstance);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', 'Ouvriers')
            ->setPageTitle('new', 'Créer un Ouvrier')
            ->setPageTitle('edit', 'Modifier un Ouvrier')
            ->setPageTitle('detail', 'Détails de l\'Ouvrier');
    }
}
