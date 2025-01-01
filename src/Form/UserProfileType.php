<?php
// src/Form/UserProfileType.php
namespace App\Form;

use App\Entity\Users;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType; // Utiliser FileType pour les images
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // Champ pour le nom d'utilisateur
            ->add('username', TextType::class, [
                'label' => 'Nom d\'utilisateur : ',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom d\'utilisateur est obligatoire.']),
                    new Assert\Length([
                        'min' => 3,
                        'minMessage' => 'Le nom d\'utilisateur doit comporter au moins {{ limit }} caractères.',
                    ]),
                ],
            ])
            
            // Champ pour l'email
            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail : ',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'L\'email est obligatoire.']),
                    new Assert\Email(['message' => 'Veuillez fournir une adresse email valide.']),
                ],
            ])
            
            // Champ pour l'image
            ->add('image', FileType::class, [
                'label' => 'Image de profil : ',
                'required' => false, // Facultatif
                'mapped' => false, // Ce champ n'est pas directement lié à une propriété de l'entité
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPEG ou PNG).',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Users::class, // Associe le formulaire à l'entité Users
        ]);
    }
}
