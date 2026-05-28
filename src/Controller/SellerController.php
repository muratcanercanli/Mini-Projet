<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\ProductRepository;
use App\Repository\ReviewRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SellerController extends AbstractController
{
    #[Route('/seller/{id}', name: 'app_seller_profile', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(
        int $id,
        UserRepository $userRepository,
        ProductRepository $productRepository,
        ReviewRepository $reviewRepository,
    ): Response {
        /** @var User|null $seller */
        $seller = $userRepository->find($id);

        if (!$seller) {
            throw $this->createNotFoundException('Vendeur introuvable.');
        }

        $products      = $productRepository->findBy(['seller' => $seller], ['creationDate' => 'DESC']);
        $reviews       = $reviewRepository->findBySeller($seller);
        $averageRating = $reviewRepository->getAverageRating($seller);

        /** @var User|null $currentUser */
        $currentUser = $this->getUser();
        $existingReview = null;

        if ($currentUser && $currentUser !== $seller) {
            $existingReview = $reviewRepository->findOneByAuthorAndSeller($currentUser, $seller);
        }

        return $this->render('seller/show.html.twig', [
            'seller'        => $seller,
            'products'      => $products,
            'reviews'       => $reviews,
            'averageRating' => $averageRating,
            'existingReview' => $existingReview,
        ]);
    }
}
