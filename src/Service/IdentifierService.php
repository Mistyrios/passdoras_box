<?php

namespace App\Service;

use App\Entity\Identifier;
use App\Repository\IdentifierRepository;

readonly class IdentifierService
{
    public function __construct(
        private IdentifierRepository $identifierRepository,
    ) {
    }

    /**
     * @return mixed[]
     */
    public function getIndentifiersByLabelAndLogin(string $label, string $login): array
    {
        return $this->identifierRepository->getIndentifiersByLabelAndLogin($label, $login);
    }

    /**
     * @return mixed[]
     */
    public function getIndentifiersByLabel(string $label): array
    {
        return $this->identifierRepository->getIndentifiersByLabel($label);
    }

    public function getIdentifierById(int $id): ?Identifier
    {
        return $this->identifierRepository->getIdentifierById($id);
    }
}
