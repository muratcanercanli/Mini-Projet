<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(Request $request, ProductRepository $productRepository, CategoryRepository $categoryRepository): Response
    {
        $filters = [
            'name'     => $request->query->get('name', ''),
            'category' => $request->query->get('category', ''),
            'priceMin' => $request->query->get('priceMin', ''),
            'priceMax' => $request->query->get('priceMax', ''),
            'stockMin' => $request->query->get('stockMin', ''),
            'stockMax' => $request->query->get('stockMax', ''),
        ];

        $products = $productRepository->findByFilters($filters);

        return $this->render('home/index.html.twig', [
            'products'   => $products,
            'categories' => $categoryRepository->findAll(),
            'filters'    => $filters,
        ]);
    }
}
