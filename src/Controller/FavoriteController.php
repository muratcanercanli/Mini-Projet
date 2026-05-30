<?php

namespace App\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/favorite')]
#[IsGranted('ROLE_USER')]
class FavoriteController extends AbstractController
{
    #[Route('/toggle/{id}', name: 'app_favorite_toggle', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggle(Request $request, Product $product, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('favorite_toggle_' . $product->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $user = $this->getUser();

        if ($user->getListFavorites()->contains($product)) {
            $user->removeListFavorite($product);
            $this->addFlash('success', '"' . $product->getName() . '" retiré de vos favoris.');
        } else {
            $user->addListFavorite($product);
            $this->addFlash('success', '"' . $product->getName() . '" ajouté à vos favoris.');
        }

        $em->flush();

        $redirect = $request->request->get('redirect', 'product');

        if ($redirect === 'profile') {
            return $this->redirectToRoute('app_profile');
        }

        return $this->redirectToRoute('app_product_show', ['id' => $product->getId()]);
    }
}
