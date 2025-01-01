<?php
// src/Form/PostType.php
namespace App\Form;

use App\Entity\Posts;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType; 
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // Informations sur l'animal
            ->add('animal_name', TextType::class, [
                'label' => 'Nom de l\'animal',
            ])
            ->add('species', TextType::class, [
                'label' => 'Espèce',
            ])
            ->add('breed', TextType::class, [
                'label' => 'Race',
                'required' => false,
            ])
            ->add('age', IntegerType::class, [
                'label' => 'Âge',
                'required' => false,
            ])
            ->add('gender', ChoiceType::class, [ 
                'label' => 'Genre', 
                'choices' => [ 
                    'Mâle' => 'Mâle', 
                    'Femelle' => 'Femelle', 
                ], 
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
            ])
            ->add('image', FileType::class, [
                'label' => 'Image',
                'required' => false,
            ])
            ->add('is_available', ChoiceType::class, [ 
                'label' => 'Disponibilité', 
                'choices' => [ 
                    'Disponible' => true, 
                    'Non disponible' => false, 
                ], 
            ])

            // Informations de contact de l'utilisateur
            ->add('phone_number', TextType::class, [
                'label' => 'Numéro de téléphone',
                'required' => false,
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Posts::class,
        ]);
    }
}
