<?php

namespace Projet\UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', 'text', array('label' => 'Mail', 'required' => true))
            ->add('lastname', 'text', array('label' => 'Nom', 'required' => true))
            ->add('firstname', 'text', array('label' => 'Prénom', 'required' => true))
            ->add('adress', 'text', array('label' => 'Adresse', 'required' => true))
            ->add('zipcode', 'text', array('label' => 'Code postale', 'required' => true))
            ->add('city', 'text', array('label' => 'Ville', 'required' => true));
}

public function getParent()
{
return 'fos_user_registration';
}

public function getName()
{
return 'projet_user_registration';
}
}
