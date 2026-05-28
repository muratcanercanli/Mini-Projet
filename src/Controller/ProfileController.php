<?php

namespace App\Controller;

use App\Repository\ReviewRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('', name: 'app_profile', methods: ['GET'])]
    public function index(ReviewRepository $reviewRepository): Response
    {
        $user = $this->getUser();

        $receivedReviews = $reviewRepository->findBySeller($user);
        $averageRating   = $reviewRepository->getAverageRating($user);
        $givenReviews    = $reviewRepository->findByAuthor($user);

        return $this->render('profile/show.html.twig', [
            'user'            => $user,
            'receivedReviews' => $receivedReviews,
            'averageRating'   => $averageRating,
            'givenReviews'    => $givenReviews,
        ]);
    }
}
