<?php

namespace App\Repository;

use App\Entity\Identifier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Identifier>
 */
class IdentifierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Identifier::class);
    }

    /**
     * @return mixed[]
     */
    public function getIndentifiersByLabelAndLogin(string $label, string $login): array
    {
        $result = $this->createQueryBuilder('i')
            ->where('i.label = :label')
            ->andWhere('i.login = :login')
            ->setParameter('label', $label)
            ->setParameter('login', $login)
            ->getQuery()
            ->getResult();

        if (is_array($result)) {
            return $result;
        }

        return [];
    }

    /**
     * @return mixed[]
     */
    public function getIndentifiersByLabel(string $label): array
    {
        $result = $this->createQueryBuilder('i')
            ->where('LOWER(i.label) LIKE LOWER(:label)')
            ->setParameter('label', '%'.strtolower($label).'%') // Ensure case insensitivity
            ->getQuery()
            ->getResult();

        if (is_array($result)) {
            return $result;
        }

        return [];
    }

    public function getIdentifierById(int $id): ?Identifier
    {
        $identifier = $this->createQueryBuilder('i')
            ->where('i.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        if ($identifier instanceof Identifier) {
            return $identifier;
        }

        return null;
    }
}
