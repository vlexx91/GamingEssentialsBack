<?php

namespace App\Controller;

use App\Entity\Producto;
use App\Repository\ProductoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/carrito')]
class CarritoController extends AbstractController
{
    #[Route('/', methods: ['GET'])]
    public function getCart(SessionInterface $session): JsonResponse
    {
        $cart = $session->get('cart', []);
        return $this->json($cart);
    }

    #[Route('/add', methods: ['POST'])]
    public function addToCart(Request $request, SessionInterface $session, ProductoRepository $productRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $productId = $data['productId'];
        $quantity = $data['quantity'];

        $cart = $session->get('cart', []);

        // Verificar si el producto existe en la base de datos
        $product = $productRepository->find($productId);
        if (!$product) {
            return $this->json(['message' => 'Producto no encontrado'], 404);
        }

        // Si el producto ya está en el carrito, incrementamos la cantidad
        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] += $quantity;
        } else {
            $cart[$productId] = [
                'id' => $product->getId(),
                'name' => $product->getNombre(),
                'price' => $product->getPrecio(),
                'quantity' => $quantity,
                'imageUrl' => $product->getImagen() // Asegúrate de tener un campo de imagen
            ];
        }

        // Guardar en sesión
        $session->set('cart', $cart);


        return $this->json(['message' => 'Producto agregado al carrito']);
    }

    #[Route('/remove/{productId}', methods: ['DELETE'])]
    public function removeFromCart(int $productId, SessionInterface $session): JsonResponse
    {
        $cart = $session->get('cart', []);

        if (isset($cart[$productId])) {
            unset($cart[$productId]);
            $session->set('cart', $cart);
        }

        return $this->json(['message' => 'Producto eliminado del carrito']);
    }

    #[Route('/clear', methods: ['POST'])]
    public function clearCart(SessionInterface $session): JsonResponse
    {
        $session->remove('cart');
        return $this->json(['message' => 'Carrito vaciado']);
    }
}