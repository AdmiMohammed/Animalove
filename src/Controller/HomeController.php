<?php
// src/Controller/HomeController.php
namespace App\Controller;

use App\Entity\Posts;
use App\Entity\Favorites;
use App\Repository\PostsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class HomeController extends AbstractController
{
    #[Route('/home', name: 'home')]
    public function index(Request $request, PostsRepository $postsRepository, EntityManagerInterface $em): Response
   {        
            $user = $this->getUser();
            $typeAnimal = $request->query->get('type_animal', '');
            $page = max($request->query->getInt('page', 1), 1);
            $limit = 10; // Nombre de publications par page
    
            // Filtrage des publications
            $query = $postsRepository->createQueryBuilder('p');
    
            if ($typeAnimal) {
                $query->where('p.species = :typeAnimal')
                      ->setParameter('typeAnimal', $typeAnimal);
            }
    
            $query->orderBy('p.id', 'DESC')
                  ->setFirstResult(($page - 1) * $limit)
                  ->setMaxResults($limit);
    
            $publications = $query->getQuery()->getResult();
    
            // Calcul du nombre total de pages
            $totalPublications = $query->select('COUNT(p.id)')->getQuery()->getSingleScalarResult();
            $totalPages = ceil($totalPublications / $limit);
    
            return $this->render('home/index.html.twig', [
                'publications' => $publications,
                'page' => $page,
                'pages' => $totalPages,
                'type_animal' => $typeAnimal,
                'user' => $user,
            ]);
        }

        #[Route('/publication/{id}/ajouter-favoris', name: 'ajouter_favoris')]
public function ajouterFavoris(Posts $post, EntityManagerInterface $em): Response
{
    // Récupérer l'utilisateur connecté
    $user = $this->getUser();

    if (!$user) {
        // Rediriger si l'utilisateur n'est pas connecté
        $this->addFlash('error', 'Vous devez être connecté pour gérer vos favoris.');
        return $this->redirectToRoute('app_login');
    }

    // Vérifier si le favori existe déjà
    $favori = $em->getRepository(\App\Entity\Favorites::class)->findOneBy([
        'user' => $user,
        'post' => $post,
    ]);

    if ($favori) {
        // Si le favori existe déjà, le supprimer
        $em->remove($favori);
        $this->addFlash('success', 'Publication retirée des favoris.');
    } else {
        // Sinon, créer un nouveau favori
        $favori = new \App\Entity\Favorites();
        $favori->setUser($user);
        $favori->setPost($post);
        $favori->setCreatedAt(new \DateTime()); // Assurez-vous que la date est bien définie
        $em->persist($favori);
        $this->addFlash('success', 'Publication ajoutée aux favoris.');
    }

    // Sauvegarder les changements dans la base de données
    $em->flush();

    // Redirection vers la page d'accueil
    return $this->redirectToRoute('home');
}

        
    
    #[Route('/post/{id}/details', name: 'post_savoir_plus')]
    public function details(int $id, EntityManagerInterface $em): Response
    {
        $post = $em->getRepository(Posts::class)->find($id);

        if (!$post) {
            throw $this->createNotFoundException('Publication introuvable.');
        }

        $comments = $em->getRepository(Comments::class)->findBy(
            ['post' => $post],
            ['created_at' => 'DESC']
        );

        return $this->render('post/details.html.twig', [
            'post' => $post,
            'comments' => $comments,
        ]);
    }

    #[Route('/post/{id}/comment', name: 'ajouter_commentaire_ajax', methods: ['POST'])]
    public function ajouterCommentaireAjax(Request $request, Posts $post, EntityManagerInterface $em): JsonResponse
    {
        // Récupère les données envoyées en JSON
        $data = json_decode($request->getContent(), true);
        $content = $data['content'] ?? '';

        // Vérifie si le contenu du commentaire est vide
        if (!$content) {
            return new JsonResponse(['error' => 'Le commentaire ne peut pas être vide.'], 400);
        }

        // Crée et enregistre le commentaire
        $comment = new Comments();
        $comment->setPost($post);
        $comment->setUser($this->getUser());
        $comment->setContent($content);
        $comment->setCreatedAt(new \DateTime());

        $em->persist($comment);
        $em->flush();

        return new JsonResponse([
            'id' => $comment->getId(),
            'content' => $comment->getContent(),
            'createdAt' => $comment->getCreatedAt()->format('d/m/Y H:i'),
            'username' => $comment->getUser()->getUsername(),
        ]);
    }

    // Méthode pour supprimer un commentaire via AJAX
    #[Route('/comment/{id}/delete', name: 'supprimer_commentaire_ajax', methods: ['DELETE'])]
    public function supprimerCommentaireAjax(Comments $comment, EntityManagerInterface $em): JsonResponse
    {
        if ($this->getUser() !== $comment->getUser()) {
            return new JsonResponse(['error' => 'Vous ne pouvez pas supprimer ce commentaire.'], 403);
        }

        $em->remove($comment);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }
    
}
