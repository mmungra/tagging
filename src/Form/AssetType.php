<?php

namespace App\Form;

use App\Entity\Asset;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AssetType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add('assetId', TextType::class, [
                'label' => 'Asset ID',
            ])
            ->add('userName', TextType::class, [
                'label' => 'Assigned User',
            ])
            ->add('pcName', TextType::class, [
                'label' => 'PC Name',
            ])
            ->add('serialNumber', TextType::class, [
                'label' => 'Serial Number',
            ])
            ->add('model', TextType::class, [
                'label' => 'Model',
            ])
            ->add('department', TextType::class, [
                'label' => 'Department',
            ])
            ->add('purchaseDate', DateType::class, [
                'label' => 'Purchase Date',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('warrantyExpiry', DateType::class, [
                'label' => 'Warranty Expiry',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('officeLocation', TextType::class, [
                'label' => 'Office Location',
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Active' => 'Active',
                    'Inactive' => 'Inactive',
                    'Damaged' => 'Damaged',
                ],
                'placeholder' => 'Select a status',
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Asset::class,
        ]);
    }
}
