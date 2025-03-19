<?php

namespace App\Controller;

use App\Form\SearchType;
use App\Service\IdentifierService;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Defuse\Crypto\Key;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomepageController extends AbstractController
{
    public function __construct(
        private readonly IdentifierService $identifierService,
    ) {
    }

    #[Route('/', name: 'app_home')]
    public function index(Request $request): Response
    {
        $form = $this->createForm(SearchType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if (is_array($data)) {
                $search = $data['search'];

                $identifiers = $this->identifierService->getIndentifiersByLabel($search);

                return $this->render('homepage/index.html.twig', [
                    'form' => $form->createView(),
                    'identifiers' => $identifiers,
                ]);
            }
        }

        return $this->render('homepage/index.html.twig', [
            'form' => $form->createView(),
            'controller_name' => 'DefaultController',
        ]);
    }

    /**
     * @throws BadFormatException
     * @throws EnvironmentIsBrokenException
     * @throws WrongKeyOrModifiedCiphertextException
     */
    #[Route('/reveal-password/{id}', name: 'reveal_password', methods: ['GET'])]
    public function revealPassword(int $id): JsonResponse
    {
        $cryptoKey = $_ENV['CRYPTO_KEY'] ?? null;

        if (!is_string($cryptoKey) || empty(trim($cryptoKey))) {
            return new JsonResponse(
                ['error' => 'Encryption key is not set or invalid'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $encryptionKey = Key::loadFromAsciiSafeString($cryptoKey);

        // Authorize user and fetch encrypted password
        $identifier = $this->identifierService->getIdentifierById($id);

        if (!$identifier) {
            return new JsonResponse(['error' => 'Identifier not found'], Response::HTTP_NOT_FOUND);
        }

        $password = $identifier->getPassword();
        $password = Crypto::decrypt($password, $encryptionKey);

        return new JsonResponse(['password' => $password]);
    }
}
