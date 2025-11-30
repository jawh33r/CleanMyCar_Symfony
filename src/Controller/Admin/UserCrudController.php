<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            TextField::new('email', 'Email')
                ->setRequired(true),
            ArrayField::new('roles', 'Roles')
                ->setHelp('User roles (ROLE_USER, ROLE_ADMIN, ROLE_OVR)')
                ->formatValue(function ($value) {
                    if (empty($value)) {
                        return 'ROLE_USER (default)';
                    }
                    return implode(', ', $value);
                }),
            AssociationField::new('client', 'Client')
                ->onlyOnIndex()
                ->formatValue(function ($value, $entity) {
                    if ($entity && $entity->getClient()) {
                        return $entity->getClient()->getNom();
                    }
                    return 'N/A';
                }),
            AssociationField::new('admin', 'Admin')
                ->onlyOnIndex()
                ->formatValue(function ($value, $entity) {
                    if ($entity && $entity->getAdmin()) {
                        return $entity->getAdmin()->getNom();
                    }
                    return 'N/A';
                }),
            AssociationField::new('ouvrier', 'Ouvrier')
                ->onlyOnIndex()
                ->formatValue(function ($value, $entity) {
                    if ($entity && $entity->getOuvrier()) {
                        return $entity->getOuvrier()->getNom();
                    }
                    return 'N/A';
                }),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', 'Users')
            ->setPageTitle('new', 'Create User')
            ->setPageTitle('edit', 'Edit User')
            ->setPageTitle('detail', 'User Details');
    }
}
