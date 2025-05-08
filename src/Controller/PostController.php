<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Entity\Comment;
use App\Entity\Reaction;
use App\Form\PostType;
use App\Form\CommentType;
use App\Repository\PostRepository;
use App\Repository\ReactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class PostController extends AbstractController
{
    #[Route('/blog', name: 'app_blog', methods: ['GET', 'POST'])]
    public function index(
        PostRepository $repository,
        ReactionRepository $reactionRepo,
        Request $request,
        PaginatorInterface $paginator,
        EntityManagerInterface $em,
        SessionInterface $session
    ): Response {
        // Get current user
        $userId = $session->get('user_id');
        $user = $userId ? $em->getRepository(User::class)->find($userId) : null;

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Search and pagination
        $searchTerm = $request->query->get('search');
        $qb = $repository->createQueryBuilder('p')
            ->where('p.title LIKE :searchTerm OR p.content LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->orderBy('p.createdAt', 'DESC');

        $pagination = $paginator->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            5
        );

        // Prepare comment forms
        $forms = [];
        foreach ($pagination as $post) {
            $comment = new Comment();
            $comment->setPost($post)
                    ->setUser($user);
            $forms[$post->getId()] = $this->createForm(CommentType::class, $comment)
                                        ->createView();
        }

        // Get reaction counts by post
        $reactionCounts = [];
        foreach ($pagination as $post) {
            $reactionCounts[$post->getId()] = $reactionRepo->getCountsByPost($post->getId());
        }

        // Define emoji mapping for reactions
        $reactionMap = [
            1 => '👍', // Like
            2 => '😍', // Love
            3 => '😂', // Haha
            4 => '😢', // Sad
            5 => '😡', // Angry
            6 => '😮', // Wow (added to match the one in adminPostDetails method)
        ];

        return $this->render('post/index.html.twig', [
            'posts' => $pagination,
            'forms' => $forms,
            'reactionCounts' => $reactionCounts,
            'reactionMap' => $reactionMap,
            'currentPage' => $pagination->getCurrentPageNumber(),
            'previous' => $pagination->getCurrentPageNumber() > 1
                               ? $pagination->getCurrentPageNumber() - 1
                               : null,
            'next' => $pagination->getCurrentPageNumber() < ceil($pagination->getTotalItemCount() / $pagination->getItemNumberPerPage())
                               ? $pagination->getCurrentPageNumber() + 1
                               : null,
            'user' => $user
        ]);
    }

    #[Route('/admin/blog', name: 'app_blog_admin', methods: ['GET', 'POST'])]
    public function indexAdmin(
        PostRepository $repository,
        Request $request,
        PaginatorInterface $paginator,
        SessionInterface $session
    ): Response {
        // Check if admin is logged in
        if (!$session->get('admin')) {
            return $this->redirectToRoute('app_login');
        }

        $searchTerm = $request->query->get('search');

        $query = $repository->createQueryBuilder('p')
            ->where('p.title LIKE :searchTerm OR p.content LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('post/indexAdmin.html.twig', [
            'posts' => $pagination,
            'currentPage' => $pagination->getCurrentPageNumber(),
            'previous' => $pagination->getCurrentPageNumber() > 1 ? $pagination->getCurrentPageNumber() - 1 : null,
            'next' => $pagination->getCurrentPageNumber() < ceil($pagination->getTotalItemCount() / $pagination->getItemNumberPerPage()) ? $pagination->getCurrentPageNumber() + 1 : null,
        ]);
    }

    #[Route('/blog/add', name: 'add_post')]
    public function add(
        Request $request,
        ManagerRegistry $doctrine,
        SessionInterface $session,
        EntityManagerInterface $em
    ): Response {
        // Get current user
        $userId = $session->get('user_id');
        $user = $userId ? $em->getRepository(User::class)->find($userId) : null;

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $post = new Post();
        $post->setUser($user);

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle file upload
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $newFilename = uniqid().'.'.$imageFile->guessExtension();

                // Move the file to the directory where images are stored
                try {
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                } catch (FileException $exception) {
                    $this->addFlash('error', 'Image upload failed: ' . $exception->getMessage());
                }

                $post->setImageUrls($newFilename);
            }

            $em = $doctrine->getManager();
            $em->persist($post);
            $em->flush();

            return $this->redirectToRoute('app_blog');
        }

        return $this->render('post/add.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    #[Route('/blog/{id}/edit', name: 'edit_post')]
    public function edit(
        Post $post,
        Request $request,
        EntityManagerInterface $em,
        SessionInterface $session
    ): Response {
        // Get current user
        $userId = $session->get('user_id');
        $user = $userId ? $em->getRepository(User::class)->find($userId) : null;

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Check if user is the owner of the post
        if ($post->getUser()->getId() !== $user->getId() && !$session->get('admin')) {
            $this->addFlash('error', 'You are not authorized to edit this post.');
            return $this->redirectToRoute('app_blog');
        }

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle file upload
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $newFilename = uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                } catch (FileException $exception) {
                    $this->addFlash('error', 'Image upload failed: ' . $exception->getMessage());
                }

                $post->setImageUrls($newFilename);
            }

            $em->flush();

            $this->addFlash('success', 'Post successfully updated.');
            return $this->redirectToRoute('app_blog');
        }

        return $this->render('post/edit.html.twig', [
            'form' => $form->createView(),
            'post' => $post,
            'user' => $user
        ]);
    }

    #[Route('/blog/{id}/delete', name: 'delete_post', methods: ['POST'])]
    public function delete(
        Request $request,
        Post $post,
        EntityManagerInterface $em,
        SessionInterface $session
    ): Response {
        // Check if admin is logged in or if user is the owner
        $userId = $session->get('user_id');
        $user = $userId ? $em->getRepository(User::class)->find($userId) : null;

        if ((!$session->get('admin')) && (!$user || $post->getUser()->getId() !== $user->getId())) {
            $this->addFlash('error', 'You are not authorized to delete this post.');
            return $this->redirectToRoute('app_blog');
        }

        // Validate CSRF token
        if (!$this->isCsrfTokenValid('delete'.$post->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');

            if ($session->get('admin')) {
                return $this->redirectToRoute('app_blog_admin');
            }

            return $this->redirectToRoute('app_blog');
        }

        $em->remove($post);
        $em->flush();

        $this->addFlash('success', 'Post successfully deleted.');

        if ($session->get('admin')) {
            return $this->redirectToRoute('app_blog_admin');
        }

        return $this->redirectToRoute('app_blog');
    }

    #[Route('/blog/{id}/comment/add', name: 'add_comment', methods: ['POST'])]
    public function addComment(
        Request $request,
        int $id,
        EntityManagerInterface $em,
        SessionInterface $session
    ): Response {
        // Get current user
        $userId = $session->get('user_id');
        $user = $userId ? $em->getRepository(User::class)->find($userId) : null;

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $post = $em->getRepository(Post::class)->find($id);
        if (!$post) {
            $this->addFlash('error', 'Post not found.');
            return $this->redirectToRoute('app_blog');
        }

        $content = trim($request->request->get('content'));
        if (empty($content)) {
            $this->addFlash('error', 'Comment cannot be empty.');
            return $this->redirectToRoute('app_blog');
        }

        $comment = new Comment();
        $comment->setContent($content)
                ->setPost($post)
                ->setUser($user);

        $em->persist($comment);
        $em->flush();

        $this->addFlash('success', 'Comment added successfully.');
        return $this->redirectToRoute('app_blog');
    }

    #[Route('/blog/comment/{id}/delete', name: 'delete_comment', methods: ['POST'])]
    public function deleteComment(
        Request $request,
        Comment $comment,
        EntityManagerInterface $em,
        SessionInterface $session
    ): Response {
        // Check if admin is logged in or if user is the owner
        $userId = $session->get('user_id');
        $user = $userId ? $em->getRepository(User::class)->find($userId) : null;

        if ((!$session->get('admin')) && (!$user || $comment->getUser()->getId() !== $user->getId())) {
            $this->addFlash('error', 'You are not authorized to delete this comment.');
            return $this->redirectToRoute('app_blog');
        }

        // Validate CSRF token
        if (!$this->isCsrfTokenValid('delete_comment'.$comment->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');

            if ($session->get('admin')) {
                return $this->redirectToRoute('app_blog_admin');
            }

            return $this->redirectToRoute('app_blog');
        }

        $em->remove($comment);
        $em->flush();

        $this->addFlash('success', 'Comment deleted successfully.');

        if ($session->get('admin')) {
            return $this->redirectToRoute('app_blog_admin');
        }

        return $this->redirectToRoute('app_blog');
    }

    #[Route('/blog/{id}/react', name: 'post_react', methods: ['POST'])]
    public function react(
        Request $request,
        Post $post,
        EntityManagerInterface $em,
        SessionInterface $session
    ): JsonResponse {
        // Log request data for debugging
        $requestData = $request->request->all();
        $debug = [
            'post_id' => $post->getId(),
            'request_data' => $requestData,
            'method' => $request->getMethod()
        ];

        // Get current user
        $userId = $session->get('user_id');
        $user = $userId ? $em->getRepository(User::class)->find($userId) : null;

        if (!$user) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'User not authenticated',
                'debug' => $debug
            ], 401);
        }

        $debug['user_id'] = $user->getId();

        $typeIndex = (int)$request->request->get('type');
        $emoji = $request->request->get('emoji');

        // Define valid reaction types
        $validReactionTypes = [1, 2, 3, 4, 5, 6];

        // Validate reaction type
        if (!in_array($typeIndex, $validReactionTypes)) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Invalid reaction type',
                'debug' => $debug
            ], 400);
        }

        $debug['type_index'] = $typeIndex;
        $debug['emoji'] = $emoji;

        // Check for existing reaction
        $existing = $em->getRepository(Reaction::class)->findOneBy([
            'post' => $post,
            'user' => $user
        ]);

        $debug['existing_reaction'] = $existing ? [
            'id' => $existing->getId(),
            'type_index' => $existing->getTypeIndex(),
            'emoji' => $existing->getEmoji()
        ] : null;

        if ($existing) {
            // If same reaction, remove it (toggle)
            if ($existing->getTypeIndex() === $typeIndex) {
                $em->remove($existing);
                $em->flush();
                $debug['action'] = 'removed';
                return new JsonResponse([
                    'status' => 'removed',
                    'debug' => $debug
                ]);
            } else {
                // If different reaction, update it
                $existing->setTypeIndex($typeIndex);
                $existing->setEmoji($emoji);
                $em->flush();
                $debug['action'] = 'updated';
                return new JsonResponse([
                    'status' => 'updated',
                    'debug' => $debug
                ]);
            }
        } else {
            // Create new reaction
            $reaction = new Reaction();
            $reaction->setPost($post)
                    ->setUser($user)
                    ->setTypeIndex($typeIndex)
                    ->setEmoji($emoji);

            $em->persist($reaction);
            $em->flush();

            $debug['action'] = 'created';
            return new JsonResponse([
                'status' => 'ok',
                'debug' => $debug
            ]);
        }
    }

    #[Route('/admin/blog/{id}/comments', name: 'view_post_comments_admin', methods: ['GET'])]
    public function viewPostCommentsAdmin(
        Post $post,
        SessionInterface $session
    ): Response {
        // Check if admin is logged in
        if (!$session->get('admin')) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('post/view_comments.html.twig', [
            'post' => $post,
            'comments' => $post->getComments(),
        ]);
    }

    #[Route('/admin/blog/{id}/details', name: 'admin_post_details', methods: ['GET'])]
    public function adminPostDetails(
        int $id,
        EntityManagerInterface $em,
        SessionInterface $session
    ): Response {
        // Check if admin is logged in
        if (!$session->get('admin')) {
            return $this->redirectToRoute('app_login');
        }

        // Find the post
        $post = $em->getRepository(Post::class)->find($id);

        if (!$post) {
            $this->addFlash('error', 'Post not found.');
            return $this->redirectToRoute('app_blog_admin');
        }

        // Get reaction counts
        $reactionCounts = $em->getRepository(Reaction::class)->getCountsByPost($post->getId());

        // Get emoji mapping
        $emojiMap = [
            1 => '👍', // Like
            2 => '❤️', // Love
            3 => '😂', // Haha
            4 => '😮', // Wow
            5 => '😢', // Sad
            6 => '😡', // Angry
        ];

        // Get all reactions for this post to get the actual emojis
        $reactions = $em->getRepository(Reaction::class)->findBy(['post' => $post]);

        // Create a map of typeIndex to emoji
        $reactionEmojis = [];
        foreach ($reactions as $reaction) {
            $reactionEmojis[$reaction->getTypeIndex()] = $reaction->getEmoji();
        }

        return $this->render('post/admin_details.html.twig', [
            'post' => $post,
            'reactionCounts' => $reactionCounts,
            'emojiMap' => $emojiMap,
            'reactionEmojis' => $reactionEmojis,
        ]);
    }
}
