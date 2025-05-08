<?php

namespace App\Controller;

use App\Entity\Feedback;
use App\Form\FeedbackType;
use App\Repository\FeedbackRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Utils\TextAnalyzer;
use App\Service\FeedbackAnalyzerService;

class FeedbackController extends AbstractController
{
    #[Route('/feedback', name: 'feedback_index')]
    public function index(FeedbackRepository $repo): Response
    {
        return $this->render('feedback/index.html.twig', [
            'feedbacks' => $repo->findAll(),
        ]);
    }

    #[Route('/feedback/new', name: 'feedback_new')]
    public function new(Request $request, EntityManagerInterface $em, SessionInterface $session): Response
    {
        $userId = $session->get('user_id');
        if (!$userId) {
            $this->addFlash('danger', 'You must be logged in to add feedback.');
            return $this->redirectToRoute('app_login');
        }

        $user = $em->getRepository(\App\Entity\User::class)->find($userId);

        $existingFeedback = $em->getRepository(Feedback::class)->findOneBy(['userId' => $user]);
        if ($existingFeedback) {
            $this->addFlash('warning', 'You have already submitted feedback.');
            return $this->redirectToRoute('user_account');
        }

        $feedback = new Feedback();
        $form = $this->createForm(FeedbackType::class, $feedback);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $feedback->setUserId($user);
            $feedback->setDate(new \DateTime());

            $em->persist($feedback);
            $em->flush();

            $this->addFlash('success', 'Feedback submitted successfully!');
            return $this->redirectToRoute('user_account');
        }

        return $this->render('feedback/new.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);        
    }

    #[Route('/feedback/edit/{id}', name: 'feedback_edit')]
    public function edit(Feedback $feedback, Request $request, EntityManagerInterface $em, SessionInterface $session): Response
    {
        $userId = $session->get('user_id');
        $user = $em->getRepository(\App\Entity\User::class)->find($userId);

        if ($feedback->getUserId()->getId() !== $userId) {
            $this->addFlash('danger', 'Unauthorized access.');
            return $this->redirectToRoute('user_account');
        }
    
        $form = $this->createForm(FeedbackType::class, $feedback);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Feedback updated successfully!');
            return $this->redirectToRoute('user_account');
        }
    
        return $this->render('feedback/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }    

    #[Route('/feedback/delete/{id}', name: 'feedback_delete')]
    public function delete(Feedback $feedback, EntityManagerInterface $em, SessionInterface $session): Response
    {
        $userId = $session->get('user_id');
        if ($feedback->getUserId()->getId() !== $userId) {
            $this->addFlash('danger', 'Unauthorized access.');
            return $this->redirectToRoute('user_account');
        }

        $em->remove($feedback);
        $em->flush();

        $this->addFlash('danger', 'Feedback deleted!');
        return $this->redirectToRoute('user_account');
    }

    #[Route('/feedback/show/{id}', name: 'feedback_show')]
    public function show(Feedback $feedback, SessionInterface $session, EntityManagerInterface $em): Response
    {
        $userId = $session->get('user_id');
        $user = $em->getRepository(\App\Entity\User::class)->find($userId);
    
        return $this->render('feedback/show.html.twig', [
            'feedback' => $feedback,
            'user' => $user,
        ]);
    }  
    
    #[Route('/admin/feedbacks', name: 'admin_feedbacks')]
    public function adminFeedbacks(FeedbackRepository $repo, SessionInterface $session): Response
    {
        if (!$session->get('admin')) {
            return $this->redirectToRoute('app_login');
        }
    
        return $this->render('feedback/feedbacks.html.twig', [
            'feedbacks' => $repo->findAll(),
        ]);
    }

    #[Route('/admin/feedbacks/clean', name: 'admin_feedbacks_clean')]
    public function cleanSpam(FeedbackRepository $repo, EntityManagerInterface $em, SessionInterface $session): Response
    {
        if (!$session->get('admin')) {
            return $this->redirectToRoute('app_login');
        }
    
        $all = $repo->findAll();
        $deleted = 0;
    
        foreach ($all as $feedback) {
            if (!TextAnalyzer::isMeaningful($feedback->getContent())) {
                $em->remove($feedback);
                $deleted++;
            }
        }
    
        $em->flush();
    
        $this->addFlash('success', "$deleted spam feedback(s) removed.");
        return $this->redirectToRoute('admin_feedbacks');
    }

    // Inject the service in constructor
    private FeedbackAnalyzerService $analyzerService;

    public function __construct(
        // Other dependencies...
        FeedbackAnalyzerService $analyzerService
    ) {
        // Other assignments...
        $this->analyzerService = $analyzerService;
    }

    // Add this to your feedback detail method
    #[Route('/admin/feedback/{id}', name: 'admin_feedback_detail')]
    public function detail(Feedback $feedback): Response
    {
        $analysis = $this->analyzerService->analyzeFeedback($feedback);
        
        return $this->render('admin/feedback/detail.html.twig', [
            'feedback' => $feedback,
            'analysis' => $analysis
        ]);
    }

    #[Route('/admin/feedback/delete/{id}', name: 'admin_feedback_delete')]
    public function adminDelete(Feedback $feedback, EntityManagerInterface $em, SessionInterface $session): Response
    {
        // Remove the admin check since we're in an admin route
        $em->remove($feedback);
        $em->flush();

        $this->addFlash('success', 'Feedback deleted successfully!');
        return $this->redirectToRoute('admin_feedbacks');
    }
}

