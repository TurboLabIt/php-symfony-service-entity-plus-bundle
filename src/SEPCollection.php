<?php
namespace TurboLabIt\ServiceEntityPlusBundle;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use TurboLabIt\Foreachable\ForeachableCollection;


abstract class SEPCollection extends ForeachableCollection
{
    const ENTITY_CLASS = null;

    //<editor-fold defaultstate="collapsed" desc="*** 🍹 Class properties ***">
    protected int $countTotalBeforePagination = 0;
    //</editor-fold>

    public function __construct(protected EntityManagerInterface $em) {}

    //<editor-fold defaultstate="collapsed" desc="*** 🏗️ load by IDs ***">
    public function load(array|int $ids) : static { return $this->internalLoad($ids, 'getById'); }

    public function loadComplete(array|int $ids) : static { return $this->internalLoad($ids, 'getByIdComplete'); }

    protected function internalLoad(array|int $ids, string $preferredMethodName) : static
    {
        $arrIds = is_array($ids) ? $ids : [$ids];
        $repository = $this->em->getRepository(static::ENTITY_CLASS);
        $entities = method_exists($repository, $preferredMethodName) ? $repository->$preferredMethodName($arrIds) : $repository->findBy(['id' => $arrIds]);
        return $this->setEntities($entities);
    }
    //</editor-fold>

    //<editor-fold defaultstate="collapsed" desc="*** 🏗️ load all ***">
    public function loadAll() : static { return $this->internalLoadAll('getAll'); }

    public function loadAlComplete() : static { return $this->internalLoadAll('getAllComplete'); }

    protected function internalLoadAll(string $preferredMethodName) : static
    {
        $repository = $this->em->getRepository(static::ENTITY_CLASS);
        $entities = method_exists($repository, $preferredMethodName) ? $repository->$preferredMethodName() : $repository->findAll();
        return $this->setEntities($entities);
    }
    //</editor-fold>

    //<editor-fold defaultstate="collapsed" desc="*** 🔍 load by Search ***">
    public function loadByComparableSearch(string $comparableText, string $fieldToCompare) : static
    {
        $arrIds = $this->em->getRepository(static::ENTITY_CLASS)->getIdsByComparableSearch($comparableText, $fieldToCompare);
        return $this->load($arrIds);
    }
    //</editor-fold>

    //<editor-fold defaultstate="collapsed" desc="*** ➕ add items ***">
    public function addId(array|int $ids) : static { return $this->internalAdd($ids, 'getById'); }

    public function addIdComplete(array|int $ids) : static { return $this->internalAdd($ids, 'getByIdComplete'); }

    protected function internalAdd(array|int $ids, string $preferredMethodName) : static
    {
        $arrIds     = is_array($ids) ? $ids : [$ids];
        $repository = $this->em->getRepository(static::ENTITY_CLASS);
        $entities   = method_exists($repository, $preferredMethodName) ? $repository->$preferredMethodName($arrIds) : $repository->findBy(['id' => $arrIds]);

        return $this->addEntities($entities);
    }
    //</editor-fold>

    //<editor-fold defaultstate="collapsed" desc="*** 🔨 setEntities ***">
    public function setEntities(?iterable $entities) : static
    {
        $this
            ->clear()
            ->addEntities($entities);

        $this->countTotalBeforePagination = $this->calculateTotalBeforePagination($entities);

        return $this;
    }


    public function addEntities(?iterable $entities) : static
    {
        $entities = empty($entities) ? [] : $entities;
        foreach($entities as $entity) {

            $id                 = (string)$entity->getId();
            $service            = $this->createService($entity);
            $this->arrData[$id] = $service;
        }

        return $this;
    }


    protected function calculateTotalBeforePagination(?iterable $entities) : int
    {
        if( is_array($entities) ) {
            return count($entities);
        }

        if( $entities instanceof Paginator ) {
            return $entities->count();
        }

        return $this->count();
    }
    //</editor-fold>

    //<editor-fold defaultstate="collapsed" desc="*** 🔢 Count ***">
    public function countTotalBeforePagination(): int { return $this->countTotalBeforePagination; }
    //</editor-fold>

    //<editor-fold defaultstate="collapsed" desc="*** 🗄️ Repository ***">
    public function getRepository() : ServiceEntityRepository { return $this->em->getRepository(static::ENTITY_CLASS); }
    //</editor-fold>


    /*
    ⚠️ Implement the following methods as if they were uncommented (different types in signatures make an abstract method unusable here)
        example here: https://github.com/TurboLabIt/TurboLab.it/blob/main/src/ServiceCollection/Cms/TagCollection.php

    public function getRepository() : SpecificTypeRepository { return parent::getRepository(); }

    public function createService(?SpecificTypeEntity $entity = null) : SpecificTypeService
    {
        return $this->factory->createTag($entity);
        ... or ...
        $service = new SpecificTypeService();
        if( !empty($entity) ) {
            $service->setEntity($entity);
        }

        return $service;
    }
    */
}
