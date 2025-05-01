<?php

namespace App\Controller\AdminCrudUser;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, '👤 Traveler Management')
            ->setPageTitle(Crud::PAGE_EDIT, fn (User $user) => sprintf('✏️ Edit %s %s', $user->getFirstName(), $user->getLastName()))
            ->setEntityLabelInSingular('Traveler')
            ->setEntityLabelInPlural('Travelers')
            ->setSearchFields(['firstName', 'lastName', 'email', 'country'])
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm()->setLabel('ID #'),
            TextField::new('firstName')->setLabel('First Name'),
            TextField::new('lastName')->setLabel('Last Name'),
            EmailField::new('email')->setLabel('Email Address'),
            TextField::new('country')->setLabel('Country')->hideOnForm(),
            TelephoneField::new('phoneNumber')->setLabel('Phone Number'),
            ChoiceField::new('gender')
                ->setChoices([
                    'Male' => 'Male',
                    'Female' => 'Female',
                ])
                ->allowMultipleChoices(false)
                ->renderExpanded(false)
                ->setLabel('Gender'),
            DateField::new('dateOfBirth')->setLabel('Date of Birth')->hideOnIndex(),
            BooleanField::new('isBanned')
                ->renderAsSwitch(true)
                ->setLabel('Ban Status'),
        ];
    }
}
