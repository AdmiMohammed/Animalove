<?php
// src/Controller/PostController.php
namespace App\Controller;
use App\Entity\Comments;
use App\Entity\Posts;
use App\Entity\Users;
use App\Form\PostType;
use App\Form\EditPostType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

class PostController extends AbstractController
{
    // Afficher les publications de l'utilisateur
    #[Route('/post', name: 'post')]
    public function index(EntityManagerInterface $em, Request $request): Response
    {
        $user = $this->getUser();
        $posts = $em->getRepository(Posts::class)->findBy(['user_id' => $user]);
       
        return $this->render('post/index.html.twig', [
            'posts' => $posts,
            'user' => $user,
        ]);
    }

    // Ajouter une publication
    #[Route('/post/ajouter', name: 'ajouter_publication')]
    public function ajouter(Request $request, EntityManagerInterface $em): Response
{
    $post = new Posts();
    $form = $this->createForm(PostType::class, $post);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $post->setUserId($this->getUser());

        // Assurez-vous que l'email et le téléphone de l'utilisateur sont ajoutés
        $user = $this->getUser();
        if ($user) {
            // Utilisez les informations de l'utilisateur pour remplir les champs
            $post->setEmail($user->getEmail());
            $phoneNumber = $post->getPhoneNumber();
        }
        $post->setCreatedAt(new \DateTime());

        // Gérer l'image (si elle est ajoutée)
        $file = $form->get('image')->getData();
        if ($file) {
            $fileName = uniqid() . '.' . $file->guessExtension();
            $file->move($this->getParameter('img_animals'), $fileName);
            $post->setImage($fileName);
        }

        $em->persist($post);
        $em->flush();

        return $this->redirectToRoute('post');
    }

    return $this->render('post/ajouter.html.twig', [
        'form' => $form->createView(),
    ]);
}


    // Modifier une publication
    #[Route('/post/modifier/{id}', name: 'modifier_publication')]
    public function modifier(
        Request $request,
        Posts $post,
        EntityManagerInterface $entityManager
    ): Response {
        // Vérifiez si l'utilisateur connecté est le propriétaire de la publication
        if ($this->getUser() !== $post->getUserId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cette publication.');
        }
    
        // Utiliser le formulaire `EditPostType`
        $form = $this->createForm(EditPostType::class, $post);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
    
            $this->addFlash('success', 'Publication modifiée avec succès !');
            return $this->redirectToRoute('post'); // Remplacez `post_liste` par la route de la liste des publications
        }
    
        return $this->render('post/modifier.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    
    

    // Supprimer une publication
    #[Route('/post/{id}/supprimer', name: 'supprimer_publication')]
    public function supprimer(int $id, EntityManagerInterface $em): Response
    {
        // Récupérer la publication en fonction de son ID
        $post = $em->getRepository(Posts::class)->find($id);

        // Vérifier si la publication existe et si l'utilisateur connecté est le propriétaire
        if (!$post || $post->getUserId() !== $this->getUser()) {
            throw $this->createNotFoundException('Publication introuvable');
        }

        // Supprimer la publication
        $em->remove($post);
        $em->flush();

        // Rediriger vers la page des publications après la suppression
        return $this->redirectToRoute('post');
    }
    
    // Méthode pour afficher les détails d'une publication (avec commentaires)
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

