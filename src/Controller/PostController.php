<?php
// src/Controller/PostController.php
namespace App\Controller;
use App\Entity\User;

use App\Entity\Post;
use App\Form\PostType;
use App\Form\CommentType;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\PostRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use App\Entity\Comment;
use Symfony\Component\HttpFoundation\JsonResponse;
use Snipe\BanBuilder\CensorWords;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twilio\Rest\Client;
use App\Entity\Reaction;
use App\Repository\ReactionRepository;


class PostController extends AbstractController
{
    #[Route('/post', name: 'app_post', methods: ['GET', 'POST'])]
    public function index(
        PostRepository $repository,
        ReactionRepository $reactionRepo,
        Request $request,
        PaginatorInterface $paginator,
        EntityManagerInterface $em
    ): Response {
        // 1) Recherche et pagination
        $searchTerm = $request->query->get('search');
        $qb = $repository->createQueryBuilder('p')
            ->where('p.title LIKE :searchTerm OR p.content LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%');
        $pagination = $paginator->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            3
        );

        // 2) Préparation des formulaires de commentaire (ton code existant)
        $forms = [];
        $user = $em->getRepository(User::class)->find(1); // utilisateur statique
        foreach ($pagination as $post) {
            $comment = new Comment();
            $comment->setPost($post)
                    ->setUser($user);
            $forms[$post->getId()] = $this->createForm(CommentType::class, $comment)
                                        ->createView();
        }

        // 3) Récupération des totaux de réactions par post
        $reactionCounts = [];
        foreach ($pagination as $post) {
            // getCountsByPost() renvoie [ typeIndex => count ]
            $reactionCounts[$post->getId()] = $reactionRepo->getCountsByPost($post->getId());
        }

        // 4) Envoi à la vue
        return $this->render('post/index.html.twig', [
            'posts'          => $pagination,
            'forms'          => $forms,
            'reactionCounts' => $reactionCounts,
            'currentPage'    => $pagination->getCurrentPageNumber(),
            'previous'       => $pagination->getCurrentPageNumber() > 1 
                                   ? $pagination->getCurrentPageNumber() - 1 
                                   : null,
            'next'           => $pagination->getCurrentPageNumber() < $pagination->getPageCount() 
                                   ? $pagination->getCurrentPageNumber() + 1 
                                   : null,
        ]);
    }
    #[Route('/comment/add/{postId}', name: 'add_comment', methods: ['POST'])]
    public function addComment(
        Request $request,
        int $postId,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): JsonResponse {
        // 0) Récupérer le contenu brut
        $rawContent = $request->request->get('content', '');
    
        // 1) Censure
        $censor = new CensorWords();
        $censor->setDictionary(['fr','it','en-us','en-uk','es']);
        $censor->setReplaceChar('*');
        $result       = $censor->censorString($rawContent);
        $cleanContent = $result['clean'];
    
        // 2) Charger le Post et l’utilisateur (statique ici id=1)
        $post = $em->getRepository(Post::class)->find($postId);
        if (!$post) {
            return new JsonResponse(['status'=>'error','message'=>'Post not found'], 404);
        }
        $user = $em->getRepository(User::class)->find(1);
    
        // 3) Créer le Commentaire
        $comment = new Comment();
        $comment
            ->setContent($cleanContent)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setPost($post)
            ->setUser($user);
    
        // 4) Extraire les mentions de la forme @123
        if (preg_match_all('/@(\d+)/', $cleanContent, $m)) {
            $userRepo = $em->getRepository(User::class);
            foreach ($m[1] as $mentionedId) {
                if ($mentioned = $userRepo->find((int)$mentionedId)) {
                    $comment->addMentionedUser($mentioned);
                }
            }
        }
    
        // 5) Persister
        $em->persist($comment);
        $em->flush();
    
        // 6) Email au propriétaire du post
        if ($post->getEmail()) {
            $email = (new Email())
                ->from('motaz.sammoud11@gmail.com')
                ->to($post->getEmail())
                ->subject('Nouveau commentaire sur votre post')
                ->text("Bonjour,\n\nUn nouveau commentaire a été ajouté :\n\n" . $comment->getContent());
            $mailer->send($email);
        }
    
        // 7) SMS via Twilio
        if ($post->getNum()) {
            $twilio = new Client($_ENV['TWILIO_SID'], $_ENV['TWILIO_TOKEN']);
            $twilio->messages->create(
                $post->getNum(),
                ['from'=>$_ENV['TWILIO_FROM'], 'body'=>'Nouveau commentaire : '.$comment->getContent()]
            );
        }
    
        // 8) Réponse JSON
        return new JsonResponse([
            'status'  => 'success',
            'comment' => [
                'id'        => $comment->getId(),
                'content'   => $comment->getContent(),
                'username'  => $user->getFirstName(),
                'createdAt' => $comment->getCreatedAt()->format('d/m/Y H:i'),
            ]
        ]);
    }
    

    

    #[Route('/postAdmin', name: 'app_postAdmin', methods: ['GET', 'POST'])]
    public function indexAdmin(PostRepository $repository, Request $request, PaginatorInterface $paginator, EntityManagerInterface $em): Response
    {
        $searchTerm = $request->query->get('search');
    
        $query = $repository->createQueryBuilder('p')
            ->where('p.title LIKE :searchTerm OR p.content LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->getQuery();
    
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            3
        );
    
        // Récupérer l'utilisateur statique dont l'id est 1
        $user = $em->getRepository(User::class)->find(1);
        
        $forms = [];
        foreach ($pagination as $post) {
            $comment = new Comment();
            $comment->setPost($post);
            // Affecter l'utilisateur statique au commentaire
            $user = $em->getRepository(User::class)->find(1);
            $comment->setUser($user);
            $forms[$post->getId()] = $this->createForm(CommentType::class, $comment)->createView();
        }
    
        return $this->render('post/indexAdmin.html.twig', [
            'posts' => $pagination,
            'forms' => $forms,
            'currentPage' => $pagination->getCurrentPageNumber(),
            'previous' => $pagination->getCurrentPageNumber() > 1 ? $pagination->getCurrentPageNumber() - 1 : null,
            'next' => $pagination->getCurrentPageNumber() < $pagination->getPageCount() ? $pagination->getCurrentPageNumber() + 1 : null,
        ]);
    }
    

    #[Route('/add_post', name: 'add_post')]
    public function add(Request $request, ManagerRegistry $doctrine): Response
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer le fichier uploadé depuis le champ imageFile
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                // Nettoyer le nom de fichier pour éviter des problèmes
                $safeFilename = preg_replace('/[^A-Za-z0-9-_]/', '', $originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                // Déplacer le fichier dans le dossier défini dans les paramètres (par exemple, "images_directory")
                try {
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // Gérer l'erreur si le déplacement échoue
                    $this->addFlash('error', 'Le téléchargement de l\'image a échoué.');
                }

                // Sauvegarder le nom du fichier dans l'entité (adaptation de ta propriété imageUrls)
                $post->setImageUrls($newFilename);
            }

            $em = $doctrine->getManager();
            $em->persist($post);
            $em->flush();
            return $this->redirectToRoute('app_post');
        }

        return $this->render('post/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // Les autres méthodes (update, delete, comments) restent inchangées
    #[Route('/update_post/{id}', name: 'update_post')]
    public function update(PostRepository $repository, int $id, Request $request, ManagerRegistry $doctrine): Response
    {
        $post = $repository->find($id);
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $doctrine->getManager();
            $em->flush();
            return $this->redirectToRoute('app_post');
        }

        return $this->render('post/update.html.twig', [
            'form' => $form->createView(), 
        ]);
    }

    #[Route('/delete_post/{id}', name: 'delete_post')]
    public function delete(PostRepository $repository, int $id, ManagerRegistry $doctrine): Response
    {
        $post = $repository->find($id);
        $em = $doctrine->getManager();
        $em->remove($post);
        $em->flush();
        return $this->redirectToRoute('app_post');
    }
 
    #[Route('/{id}/comments', name: 'post_comments', methods: ['GET', 'POST'])]
    public function showComments(Post $post, Request $request, EntityManagerInterface $em): Response
    {
        $comment = new Comment();
        // Modification ici : utiliser setCreatedAt au lieu de setCreated
        $comment->setCreatedAt(new \DateTime());
        $comment->setPost($post);

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($comment);
            $em->flush();
            return $this->redirectToRoute('app_post');
        }

        return $this->redirectToRoute('app_post');
    }
    #[Route('/post/{id}/comments', name: 'post_admin_comments', methods: ['POST'])]
public function showCommentsAdmin(Post $post, Request $request, EntityManagerInterface $em): Response
{
    $comment = new Comment();
    $comment->setCreatedAt(new \DateTime());
    $comment->setPost($post);

    $user = $em->getRepository(User::class)->find(1);
    $comment->setUser($user);

    $form = $this->createForm(CommentType::class, $comment);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $em->persist($comment);
        $em->flush();
    }

    return $this->redirectToRoute('app_postAdmin');
}

#[Route('/comment/delete/{id}', name: 'delete_comment')]
public function deleteComment(Comment $comment, EntityManagerInterface $em): Response
{
    $em->remove($comment);
    $em->flush();
    return $this->redirectToRoute('app_post');
}

#[Route('/postAdmin/comment/delete/{id}', name: 'delete_comment_admin')]
public function deleteCommentAdmin(Comment $comment, EntityManagerInterface $em): Response
{
    $em->remove($comment);
    $em->flush();
    return $this->redirectToRoute('app_postAdmin');
}

#[Route('/comment/update/{id}', name: 'update_comment', methods: ['POST'])]
public function updateComment(Comment $comment, Request $request, EntityManagerInterface $em): Response
{
    $newContent = $request->request->get('comment_content');

    if ($newContent) {
        $comment->setContent($newContent);
        $em->flush();
    }

    return $this->redirectToRoute('app_post');
}
#[Route('/commentAdmin/update/{id}', name: 'update_comment_admin', methods: ['POST'])]
public function updateCommentAdmin(Comment $comment, Request $request, EntityManagerInterface $em): Response
{
    $newContent = $request->request->get('comment_content');

    if ($newContent) {
        $comment->setContent($newContent);
        $em->flush();
    }

    return $this->redirectToRoute('app_post');
}
#[Route('/postAdmin/{id}/comments/view', name: 'view_post_comments_admin', methods: ['GET'])]
public function viewPostCommentsAdmin(Post $post): Response
{
    return $this->render('post/view_comments.html.twig', [
        'post' => $post,
        'comments' => $post->getComments(),
    ]);
}

#[Route('/post/{id}/react', name: 'post_react', methods: ['POST'])]
public function react(Request $request, Post $post, EntityManagerInterface $em): JsonResponse
{$typeIndex = (int)$request->request->get('type');

    $emoji = $request->request->get('emoji');
    $user = $em->getRepository(User::class)->find(1); // ou utilisateur connecté

    // Optionnel : éviter les réactions multiples du même user
    $existing = $em->getRepository(Reaction::class)->findOneBy([
        'post' => $post,
        'user' => $user
    ]);
    if ($existing) {
        if ($existing->getTypeIndex() === $typeIndex) {
            $em->remove($existing);
            $em->flush();
            return new JsonResponse(['status'=>'removed']);
        }
        $existing->setTypeIndex($typeIndex);
        $existing->setEmoji($emoji);
    } else {
        $reaction = new Reaction();
        $reaction->setTypeIndex($typeIndex);

        $reaction->setEmoji($emoji);
        $reaction->setPost($post);
        $reaction->setUser($user);
        $em->persist($reaction);
    }

    $em->flush();

    return new JsonResponse(['status' => 'ok']);
}


}
// src/Controller/PostController.php