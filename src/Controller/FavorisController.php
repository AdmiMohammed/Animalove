<?php

namespace App\Controller;

use App\Entity\Favorites;
use App\Entity\Posts;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FavorisController extends AbstractController
{
    #[Route('/favoris', name: 'favoris')]
    public function favoris(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour voir vos favoris.');
            return $this->redirectToRoute('app_login');
        }

        // Utilisation de la classe Favorites et non Favorite
        $favorites = $entityManager->getRepository(Favorites::class)
            ->findBy(['user' => $user]);

        // Récupération des posts associés aux favoris
        $posts = array_map(fn(Favorites $favorite) => $favorite->getPost(), $favorites);

        return $this->render('favoris/index.html.twig', [
            'posts' => $posts,
            'user' => $user,
        ]);
    }

    #[Route('/supprimer-favori/{id}', name: 'remove_favorite', methods: ['POST'])]
    public function removeFavorite(Posts $post, EntityManagerInterface $entityManager): RedirectResponse
    {
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour gérer vos favoris.');
            return $this->redirectToRoute('app_login');
        }

        // Utilisation de la classe Favorites et non Favorite
        $favorite = $entityManager->getRepository(Favorites::class)
            ->findOneBy(['user' => $user, 'post' => $post]);

        if ($favorite) {
            $entityManager->remove($favorite);
            $entityManager->flush();

            $this->addFlash('success', 'Publication supprimée des favoris.');
        } else {
            $this->addFlash('error', 'Ce favori n\'existe pas.');
        }

        return $this->redirectToRoute('favoris');
    }
}
