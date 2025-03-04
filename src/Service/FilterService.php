<?php

namespace App\Service;

use App\Repository\CategoryRepository;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;

class FilterService
{
    private EntityManagerInterface $entityManager;
    private CategoryRepository $categoryRepository;
    private TagRepository $tagRepository;

    public function __construct(EntityManagerInterface $entityManager, CategoryRepository $categoryRepository, TagRepository $tagRepository)
    {
        $this->entityManager = $entityManager;
        $this->categoryRepository = $categoryRepository;
        $this->tagRepository = $tagRepository;
    }

    public function findCategoryBySlug(string $slug)
    {
        return $this->categoryRepository->findOneBy(['slug' => $slug]);
    }

    public function findAllCategories(): array
    {
        return $this->categoryRepository->findAll();
    }

    public function findTagBySlug(string $slug)
    {
        // First try direct match
        $tag = $this->tagRepository->findOneBy(['slug' => $slug]);
        
        // If not found and slug contains dashes, try with spaces instead of dashes
        if (!$tag && strpos($slug, '-') !== false) {
            $alternativeSlug = str_replace('-', ' ', $slug);
            $tag = $this->tagRepository->findOneBy(['slug' => $alternativeSlug]);
        }
        
        // If not found and slug contains spaces, try with dashes instead of spaces
        if (!$tag && strpos($slug, ' ') !== false) {
            $alternativeSlug = str_replace(' ', '-', $slug);
            $tag = $this->tagRepository->findOneBy(['slug' => $alternativeSlug]);
        }
        
        return $tag;
    }

    public function findAllTags(): array
    {
        return $this->tagRepository->findAllOrderedBySlug();
    }

    public function findAllYears()
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('e.pubyear')
            ->from('App\Entity\Link', 'e')
            ->orderBy('e.pubyear', 'desc')
            ->groupBy('e.pubyear');
        return $qb->getQuery()->getResult();
    }

    public function findLinks(string|null $category, string|null $year, string|null $tag)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb
            ->select('e')
            ->from('App\Entity\Link', 'e');
        if ($category) {
            $qb->join('e.category', 'c', 'WITH', $qb->expr()->in('c.id', $category));
        }
        if ($tag) {
            $qb->join('e.tags', 't', 'WITH', $qb->expr()->in('t.id', $tag));
        }
        $qb->andWhere('e.deleted IS NULL')
            ->orderBy('e.pubdate', 'desc');

        if ($year) {
            $qb->andWhere('e.pubyear = :year')
                ->setParameter('year', $year);
        }

        return $qb->getQuery()->getResult();
    }
}