<?php
namespace TurboLabIt\ServiceEntityPlusBundle;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Result;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;


abstract class SEPRepository extends ServiceEntityRepository
{
    const string ENTITY_CLASS               = '';
    const string ID_FIELD                   = 't.id';
    const string DEFAULT_INDEXED_BY         = 't.id';
    const string DEFAULT_ORDER_BY           = '';
    const string DEFAULT_ORDER_DIRECTION    = 'DESC';

    //<editor-fold defaultstate="collapsed" desc="*** 🍹 Class properties ***">
    protected array $arrEntityCache         = [];
    protected array $arrAllEntitiesCache    = [];
    //</editor-fold>


    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, static::ENTITY_CLASS); }


    //<editor-fold defaultstate="collapsed" desc="*** 👷 Query Builders ***">
    protected function getQueryBuilder() : QueryBuilder
    {
        return $this->addDefaultOrderBy( $this->createQueryBuilder('t', static::DEFAULT_INDEXED_BY) );
    }


    protected function getQueryBuilderComplete() : QueryBuilder { return $this->getQueryBuilder(); }


    public function getQueryBuilderFromSqlQuery(string $sqlToSelectIds, array $arrSqlSelectParams = []) : ?QueryBuilder
    {
        $arrIds = $this->getIdsFromSqlQuery($sqlToSelectIds, $arrSqlSelectParams);

        if( empty($arrIds) ) {
            return null;
        }

        return
            $this->getQueryBuilder()
                ->andWhere(static::ID_FIELD . ' IN (:ids)')
                    ->setParameter("ids", $arrIds);
    }


    public function getQueryBuilderCompleteFromSqlQuery(string $sqlToSelectIds, array $arrSqlSelectParams = []) : ?QueryBuilder
    {
        $arrIds = $this->getIdsFromSqlQuery($sqlToSelectIds, $arrSqlSelectParams);

        if( empty($arrIds) ) {
            return null;
        }

        return
            $this->getQueryBuilderComplete()
                ->andWhere(static::ID_FIELD . ' IN (:ids)')
                    ->setParameter("ids", $arrIds);
    }


    protected function addDefaultOrderBy(QueryBuilder $qb) : QueryBuilder
    {
        if( !empty(static::DEFAULT_ORDER_BY) ) {
            $qb->orderBy(static::DEFAULT_ORDER_BY, static::DEFAULT_ORDER_DIRECTION);
        }

        return $qb;
    }


    protected function getTableName(string $wrapper = "`") : string
    {
        return $wrapper . $this->getEntityManager()->getClassMetadata($this->getClassName())->getTableName() . $wrapper;
    }
    //</editor-fold>

    //<editor-fold defaultstate="collapsed" desc="*** 🗄️ SQL ***">
    public function getIdsFromSqlQuery(string $sqlToSelectIds, array $arrSqlSelectParams = []) : array
    {
        $arrIds = $this->sqlQueryExecute($sqlToSelectIds, $arrSqlSelectParams)->fetchFirstColumn();

        if( empty($arrIds) ) {
            return [];
        }

        return $arrIds;
    }


    public function getIdsByComparableSearch(string $comparableText, string $fieldToCompare) : array
    {
        $sqlToSelectIds = "
            SELECT id FROM " . $this->getTableName() . "
            WHERE REGEXP_REPLACE(LOWER(`$fieldToCompare`), '[^a-z0-9]', '') = :comparableText
        ";

        return $this->getIdsFromSqlQuery($sqlToSelectIds, ['comparableText' => $comparableText]);
    }


    protected function sqlQueryExecute(string $sqlQuery, array $arrParams = []) : Result
    {
        $stmt = $this->getEntityManager()->getConnection()->prepare($sqlQuery);
        foreach($arrParams as $param => $value) {
            $stmt->bindValue($param, $value);
        }

        return $stmt->executeQuery();
    }
    //</editor-fold>

    //<editor-fold defaultstate="collapsed" desc="*** 🦠 Parameters builder ***">
    protected function prepareParamForLikeCondition(string $input) : string
    {
        $escapeChar = '\\';

        // escape the escape character itself FIRST to avoid double-escaping
        $escapedTerm = str_ireplace($escapeChar, $escapeChar . $escapeChar, $input);

        // escape the LIKE wildcard characters
        $escapedTerm = str_replace(['%', '_'], [$escapeChar . '%', $escapeChar . '_'], $escapedTerm);

        return $escapedTerm;
    }
    //</editor-fold>

    //<editor-fold defaultstate="collapsed" desc="** 📝 Updaters **">
    public function countOneView(int $entityId) : void { $this->increase("views", $entityId); }

    protected function increase(string $fieldName, int $entityId, int $increaseOf = 1) : void
    {
        $sqlQuery =
            "UPDATE " . $this->getTableName() . " " .
            "SET `" . $fieldName . "` = `" . $fieldName . "` + $increaseOf " .
            "WHERE id = :id";

        $this->sqlQueryExecute($sqlQuery, ["id" => $entityId]);
    }
    //</editor-fold>

    //<editor-fold defaultstate="collapsed" desc="*** ⚡ Cached items ***">
    protected function getFromCache(array $arrIds) : false|array
    {
        $arrFromCache = [];
        foreach($arrIds as $id) {

            $id = (string)$id;

            if( array_key_exists($id, $this->arrEntityCache) ) {

                $arrFromCache[$id] = $this->arrEntityCache[$id];

            } else {

                return false;
            }
        }

        return $arrFromCache;
    }


    public function selectOrNull(?int $id) : mixed
    {
        if( empty($id) ) {
            return null;
        }

        $id = (string)$id;
        return $this->arrAllEntitiesCache[$id] ?? $this->arrEntityCache[$id] ?? null;
    }


    public function selectOrNew(?int $id) : mixed
    {
        $entity = $this->selectOrNull($id);
        if( !empty($entity) ) {
            return $entity;
        }

        $entityName = $this->getEntityName();
        $newEntity  = new $entityName();

        if( !empty($id) && method_exists($newEntity, 'setId') ) {
            $newEntity->setId($id);
        }

        return $newEntity;
    }
    //</editor-fold>

    //<editor-fold defaultstate="collapsed" desc="*** 🔎 get by IDs ***">
    public function getOneById(int $id) : mixed
    {
        $arrItems = $this->getById([$id]);
        return reset($arrItems);
    }


    public function getOneByIdComplete(int $id) : mixed
    {
        $arrItems = $this->getByIdComplete([$id]);
        return reset($arrItems);
    }


    public function getById(array $arrIds) : array { return $this->internalGetById($this->getQueryBuilder(), $arrIds); }


    public function getByIdComplete(array $arrIds) : array
    {
        $arrResults = $this->internalGetById($this->getQueryBuilderComplete(), $arrIds);
        $this->arrEntityCache = array_merge($this->arrEntityCache, $arrResults);
        return $arrResults;
    }

    protected function internalGetById(QueryBuilder $qb, array $arrIds) : array
    {
        $arrIdsToLoad = array_unique($arrIds);
        $arrIdsToLoad = array_filter($arrIdsToLoad);

        if( empty($arrIdsToLoad) ) {
            return [];
        }

        $arrFromCache = $this->getFromCache($arrIdsToLoad);
        if( !empty($arrFromCache) ) {
            return $arrFromCache;
        }

        $arrEntitiesUnorderd =
            $qb
                ->andWhere(static::ID_FIELD . ' IN(:ids)')
                    ->setParameter('ids', $arrIdsToLoad)
                ->getQuery()->getResult();

        $arrEntities = [];
        foreach($arrIdsToLoad as $id) {

            $id = (string)$id;

            if( !array_key_exists($id, $arrEntitiesUnorderd) ) {
                continue;
            }

            $arrEntities[$id] = $arrEntitiesUnorderd[$id];
        }

        return $arrEntities;
    }
    //</editor-fold>

    //<editor-fold defaultstate="collapsed" desc="*** 🔎 get all ***">
    public function getAll() : array { return $this->internalGetAll($this->getQueryBuilder()); }


    public function getAllComplete() : array
    {
        return $this->arrAllEntitiesCache = $this->arrEntityCache = $this->internalGetAll($this->getQueryBuilderComplete());
    }

    protected function internalGetAll(QueryBuilder $qb) : array
    {
        if( !empty($this->arrAllEntitiesCache) ) {
            return $this->arrAllEntitiesCache;
        }

        return $qb->getQuery()->getResult();
    }
    //</editor-fold>
}
