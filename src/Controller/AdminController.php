<?php

namespace App\Controller;

use App\Entity\Product;
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

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('', name: 'app_admin_dashboard', methods: ['GET'])]
    public function dashboard(UserRepository $userRepository, ReviewRepository $reviewRepository): Response
    {
        $users = $userRepository->findAll();

        $usersData = [];
        foreach ($users as $user) {
            $usersData[] = [
                'user'          => $user,
                'averageRating' => $reviewRepository->getAverageRating($user),
            ];
        }

        return $this->render('admin/dashboard.html.twig', [
            'usersData' => $usersData,
        ]);
    }

    #[Route('/user/{id}', name: 'app_admin_user_detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function userDetail(User $user, ReviewRepository $reviewRepository): Response
    {
        return $this->render('admin/user_detail.html.twig', [
            'targetUser'    => $user,
            'averageRating' => $reviewRepository->getAverageRating($user),
        ]);
    }

    #[Route('/user/{id}/edit', name: 'app_admin_user_edit', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function editUser(User $user, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('admin_user_edit_' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_user_detail', ['id' => $user->getId()]);
        }

        $user->setName(trim((string) $request->request->get('name', $user->getName())));
        $user->setSurname(trim((string) $request->request->get('surname', $user->getSurname())));
        $user->setEmail(trim((string) $request->request->get('email', $user->getEmail())));
        $user->setAddress(trim((string) $request->request->get('address', $user->getAddress())));
        $user->setPhoneNumber(trim((string) $request->request->get('phoneNumber', $user->getPhoneNumber())));

        $isAdmin = $request->request->get('is_admin') === '1';
        $user->setRoles($isAdmin ? ['ROLE_ADMIN'] : []);

        $em->flush();

        $this->addFlash('success', 'Utilisateur mis à jour.');
        return $this->redirectToRoute('app_admin_user_detail', ['id' => $user->getId()]);
    }

    #[Route('/user/{id}/delete', name: 'app_admin_user_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteUser(User $user, Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $currentAdmin */
        $currentAdmin = $this->getUser();

        if ($user === $currentAdmin) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('app_admin_user_detail', ['id' => $user->getId()]);
        }

        if (!$this->isCsrfTokenValid('admin_user_delete_' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_user_detail', ['id' => $user->getId()]);
        }

        // Unlink carts: keep order history but remove the user reference
        foreach ($user->getCarts() as $cart) {
            $cart->setBuyer(null);
        }

        // Delete products and their cart items (no cascade defined on these relations)
        foreach ($user->getProducts() as $product) {
            foreach ($product->getCartItems() as $cartItem) {
                $em->remove($cartItem);
            }
            $em->remove($product);
        }

        // Reviews are removed via orphanRemoval on givenReviews / receivedReviews
        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        return $this->redirectToRoute('app_admin_dashboard');
    }

    #[Route('/product/{id}/delete', name: 'app_admin_product_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteProduct(Product $product, Request $request, EntityManagerInterface $em): Response
    {
        $sellerId = $product->getSeller()?->getId();

        if (!$this->isCsrfTokenValid('admin_product_delete_' . $product->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
        } else {
            foreach ($product->getCartItems() as $cartItem) {
                $em->remove($cartItem);
            }
            $em->remove($product);
            $em->flush();
            $this->addFlash('success', 'Produit supprimé.');
        }

        if ($sellerId) {
            return $this->redirectToRoute('app_admin_user_detail', ['id' => $sellerId]);
        }

        return $this->redirectToRoute('app_admin_dashboard');
    }

    #[Route('/review/{id}/delete', name: 'app_admin_review_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteReview(Review $review, Request $request, EntityManagerInterface $em): Response
    {
        $userId = $request->request->get('redirect_user_id');

        if (!$this->isCsrfTokenValid('admin_review_delete_' . $review->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
        } else {
            $em->remove($review);
            $em->flush();
            $this->addFlash('success', 'Commentaire supprimé.');
        }

        if ($userId) {
            return $this->redirectToRoute('app_admin_user_detail', ['id' => (int) $userId]);
        }

        return $this->redirectToRoute('app_admin_dashboard');
    }
}
