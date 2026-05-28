<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartItems;
use App\Repository\CartItemsRepository;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/cart')]
#[IsGranted('ROLE_USER')]
class CartController extends AbstractController
{
    #[Route('', name: 'app_cart_show', methods: ['GET'])]
    public function show(CartRepository $cartRepository): Response
    {
        $cart = $cartRepository->findActiveCartForUser($this->getUser());

        return $this->render('cart/show.html.twig', [
            'cart' => $cart,
        ]);
    }

    #[Route('/add/{id}', name: 'app_cart_add', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function add(
        int $id,
        Request $request,
        ProductRepository $productRepository,
        CartRepository $cartRepository,
        CartItemsRepository $cartItemsRepository,
        EntityManagerInterface $em,
    ): Response {
        $product = $productRepository->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Produit introuvable.');
        }

        if (!$this->isCsrfTokenValid('cart_add_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_product_show', ['id' => $id]);
        }

        $quantity = max(1, (int) $request->request->get('quantity', 1));

        $cart = $cartRepository->findActiveCartForUser($this->getUser());
        if (!$cart) {
            $now  = new \DateTime();
            $cart = new Cart();
            $cart->setBuyer($this->getUser())
                 ->setCreationDate($now)
                 ->setModificationDate($now);
            $em->persist($cart);
        }

        $existingItem = $cartItemsRepository->findOneBy(['cart' => $cart, 'product' => $product]);

        $currentQty = $existingItem ? $existingItem->getQuantity() : 0;
        $totalQty   = $currentQty + $quantity;

        if ($totalQty > $product->getStock()) {
            $this->addFlash('error', sprintf(
                'Stock insuffisant. Il reste %d exemplaire(s) disponible(s) (vous en avez déjà %d dans le panier).',
                $product->getStock(),
                $currentQty
            ));
            return $this->redirectToRoute('app_product_show', ['id' => $id]);
        }

        if ($existingItem) {
            $existingItem->setQuantity($totalQty);
        } else {
            $item = new CartItems();
            $item->setCart($cart)->setProduct($product)->setQuantity($quantity);
            $em->persist($item);
        }

        $cart->setModificationDate(new \DateTime());
        $em->flush();

        $this->addFlash('success', sprintf('"%s" ajouté au panier.', $product->getName()));

        return $this->redirectToRoute('app_product_show', ['id' => $id]);
    }

    #[Route('/item/{id}/quantity', name: 'app_cart_update_quantity', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function updateQuantity(
        CartItems $cartItem,
        Request $request,
        EntityManagerInterface $em,
        CartRepository $cartRepository,
    ): Response {
        $cart = $cartRepository->findActiveCartForUser($this->getUser());

        if (!$cart || $cartItem->getCart() !== $cart) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('cart_qty_' . $cartItem->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_cart_show');
        }

        $quantity = (int) $request->request->get('quantity', 1);

        if ($quantity <= 0) {
            $em->remove($cartItem);
            $cart->setModificationDate(new \DateTime());
            $em->flush();
            $this->addFlash('success', 'Article retiré du panier.');
            return $this->redirectToRoute('app_cart_show');
        }

        $product = $cartItem->getProduct();
        if ($quantity > $product->getStock()) {
            $this->addFlash('error', sprintf(
                'Stock insuffisant. Maximum disponible : %d.',
                $product->getStock()
            ));
            return $this->redirectToRoute('app_cart_show');
        }

        $cartItem->setQuantity($quantity);
        $cart->setModificationDate(new \DateTime());
        $em->flush();

        return $this->redirectToRoute('app_cart_show');
    }

    #[Route('/item/{id}/remove', name: 'app_cart_remove_item', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function removeItem(
        CartItems $cartItem,
        Request $request,
        EntityManagerInterface $em,
        CartRepository $cartRepository,
    ): Response {
        $cart = $cartRepository->findActiveCartForUser($this->getUser());

        if (!$cart || $cartItem->getCart() !== $cart) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('cart_remove_' . $cartItem->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_cart_show');
        }

        $em->remove($cartItem);
        $cart->setModificationDate(new \DateTime());
        $em->flush();

        $this->addFlash('success', 'Article retiré du panier.');

        return $this->redirectToRoute('app_cart_show');
    }

    #[Route('/checkout', name: 'app_cart_checkout', methods: ['POST'])]
    public function checkout(
        Request $request,
        CartRepository $cartRepository,
        EntityManagerInterface $em,
    ): Response {
        $cart = $cartRepository->findActiveCartForUser($this->getUser());

        if (!$cart || $cart->getCartItems()->isEmpty()) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('app_cart_show');
        }

        if (!$this->isCsrfTokenValid('cart_checkout', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_cart_show');
        }

        // Vérifie le stock pour chaque article avant de valider
        foreach ($cart->getCartItems() as $item) {
            $product = $item->getProduct();
            if ($item->getQuantity() > $product->getStock()) {
                $this->addFlash('error', sprintf(
                    'Stock insuffisant pour "%s" : %d demandé(s), %d disponible(s).',
                    $product->getName(),
                    $item->getQuantity(),
                    $product->getStock()
                ));
                return $this->redirectToRoute('app_cart_show');
            }
        }

        // Réduit les stocks et clôture le panier
        foreach ($cart->getCartItems() as $item) {
            $product = $item->getProduct();
            $product->setStock($product->getStock() - $item->getQuantity());
            $product->setModificationDate(new \DateTime());
        }

        $cart->setPurchasedAt(new \DateTime());
        $cart->setModificationDate(new \DateTime());
        $em->flush();

        $this->addFlash('success', 'Commande validée ! Merci pour votre achat.');

        return $this->redirectToRoute('app_home');
    }
}
