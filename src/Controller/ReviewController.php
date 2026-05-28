<?php

namespace App\Controller;

use App\Entity\Review;
use App\Entity\User;
use App\Repository\ReviewRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/review')]
class ReviewController extends AbstractController
{
    #[Route('/add/{id}', name: 'app_review_add', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function add(
        int $id,
        Request $request,
        UserRepository $userRepository,
        ReviewRepository $reviewRepository,
        EntityManagerInterface $em,
    ): Response {
        /** @var User $seller */
        $seller = $userRepository->find($id);

        if (!$seller) {
            throw $this->createNotFoundException('Vendeur introuvable.');
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if ($currentUser === $seller) {
            $this->addFlash('error', 'Vous ne pouvez pas vous noter vous-même.');
            return $this->redirectToRoute('app_profile');
        }

        if (!$this->isCsrfTokenValid('review_add_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_profile');
        }

        $rating  = (int) $request->request->get('rating', 0);
        $comment = trim((string) $request->request->get('comment', ''));

        if ($rating < 1 || $rating > 5) {
            $this->addFlash('error', 'La note doit être comprise entre 1 et 5.');
            return $this->redirectToRoute('app_profile');
        }

        if ($comment === '') {
            $this->addFlash('error', 'Le commentaire ne peut pas être vide.');
            return $this->redirectToRoute('app_profile');
        }

        $review = $reviewRepository->findOneByAuthorAndSeller($currentUser, $seller);

        if ($review) {
            $review->setRating($rating)->setComment($comment)->setCreatedAt(new \DateTime());
            $this->addFlash('success', 'Votre avis a été mis à jour.');
        } else {
            $review = new Review();
            $review->setAuthor($currentUser)
                   ->setSeller($seller)
                   ->setRating($rating)
                   ->setComment($comment)
                   ->setCreatedAt(new \DateTime());
            $em->persist($review);
            $this->addFlash('success', 'Votre avis a été publié.');
        }

        $em->flush();

        $redirect = $request->request->get('redirect', 'profile');

        if ($redirect === 'seller' && ($sellerId = $request->request->get('seller_id'))) {
            return $this->redirectToRoute('app_seller_profile', ['id' => (int) $sellerId]);
        }

        return $this->redirectToRoute('app_profile');
    }

    #[Route('/{id}/delete', name: 'app_review_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(
        Review $review,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if ($review->getAuthor() !== $currentUser && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cet avis.');
        }

        if (!$this->isCsrfTokenValid('review_delete_' . $review->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
        } else {
            $em->remove($review);
            $em->flush();
            $this->addFlash('success', 'Avis supprimé.');
        }

        $referer = $request->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_profile');
    }
}
