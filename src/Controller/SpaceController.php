<?php
// src/Controller/SpaceController.php
namespace App\Controller;

use App\Form\UserProfileType;
use App\Entity\Users;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;

class SpaceController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/space', name: 'app_space')]
    public function index(Request $request): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(UserProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupération des données du formulaire
            $newUsername = $form->get('username')->getData();
            $newEmail = $form->get('email')->getData();

            // Vérifier si l'email est déjà utilisé par un autre utilisateur
            $existingUser = $this->entityManager->getRepository(Users::class)
                ->findOneBy(['email' => $newEmail]);

            if ($existingUser && $existingUser !== $user) {
                // Affichez un message d'erreur si l'email est déjà pris
                $this->addFlash('error', 'Cet email est déjà utilisé par un autre compte.');

                return $this->redirectToRoute('app_space');
            }

            // Mise à jour du nom d'utilisateur et de l'email
            $user->setUsername($newUsername);
            $user->setEmail($newEmail);

            // Gestion de l'image de profil
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                // Générer un nom de fichier unique pour l'image
                $imageFileName = $this->generateUniqueFileName() . '.' . $imageFile->guessExtension();
                // Déplacer l'image téléchargée dans le répertoire "public/img_users"
                $imageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/img_users',
                    $imageFileName
                );
                $user->setImage($imageFileName);
            }

            // Persister les changements dans la base de données
            $this->entityManager->flush();

            // Ajouter un message flash
            $this->addFlash('success', 'Votre profil a été mis à jour.');

            return $this->redirectToRoute('app_space');
        }

        return $this->render('space/index.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    private function generateUniqueFileName(): string
    {
        return md5(uniqid());
    }
}
