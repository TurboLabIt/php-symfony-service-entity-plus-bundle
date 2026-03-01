<?php
namespace TurboLabIt\ServiceEntityPlusBundle;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Result;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;


abstract class SEPRepository extends ServiceEntityRepository
{
    const string ENTITY_CLASS               = '';
    const string ID_FIELD                   = 't.id';
    const string DEFAULT_INDEXED_BY         = 't.id';
    const string TITLE_FIELD                = 't.title';
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


    public function getQueryBuilderFromSqlQuery(string $sqlToSelectIds, array $arrSqlSelectParams = [], array $arrSqlSelectParamsTypes = []) : ?QueryBuilder
    {
        $arrIds = $this->getIdsFromSqlQuery($sqlToSelectIds, $arrSqlSelectParams, $arrSqlSelectParamsTypes);

        if( empty($arrIds) ) {
            return null;
        }

        return
            $this->getQueryBuilder()
                ->andWhere(static::ID_FIELD . ' IN (:ids)')
                    ->setParameter("ids", $arrIds);
    }


    public function getQueryBuilderCompleteFromSqlQuery(string $sqlToSelectIds, array $arrSqlSelectParams = [], array $arrSqlSelectParamsTypes = []) : ?QueryBuilder
    {
        $arrIds = $this->getIdsFromSqlQuery($sqlToSelectIds, $arrSqlSelectParams, $arrSqlSelectParamsTypes);

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
    public function countFromSqlQuery(string $sqlCountQuery, array $arrSqlSelectParams = [], array $arrSqlSelectParamsTypes = []) : int
    {
        $result = $this->sqlQueryExecute($sqlCountQuery, $arrSqlSelectParams, $arrSqlSelectParamsTypes)->fetchFirstColumn();
        return reset($result);
    }


    public function getIdsFromSqlQuery(string $sqlToSelectIds, array $arrSqlSelectParams = [], array $arrSqlSelectParamsTypes = []) : array
    {
        $arrIds = $this->sqlQueryExecute($sqlToSelectIds, $arrSqlSelectParams, $arrSqlSelectParamsTypes)->fetchFirstColumn();

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


    protected function sqlQueryExecute(string $sqlQuery, array $arrParams = [], array $arrParamTypes = []) : Result
    {
        return $this->getEntityManager()->getConnection()->executeQuery($sqlQuery, $arrParams, $arrParamTypes);
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

    //<editor-fold defaultstate="collapsed" desc="** 💾 Storage **">
    public function save(mixed $entity, bool $persist = true) : static
    {
        $this->getEntityManager()->persist($entity);

        if($persist) {
            $this->getEntityManager()->flush();
        }

        return $this;
    }


    public function delete(mixed $entity, bool $persist = true) : static
    {
        $this->getEntityManager()->remove($entity);

        if($persist) {
            $this->getEntityManager()->flush();
        }

        return $this;
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

    //<editor-fold defaultstate="collapsed" desc="*** 🔎 get by title ***">
    public function getOneByTitle(string $title) : mixed
    {
        $arrItems = $this->getByTitle([$title]);
        return reset($arrItems);
    }


    public function getOneByTitleComplete(string $title) : mixed
    {
        $arrItems = $this->getByTitleComplete([$title]);
        return reset($arrItems);
    }


    public function getByTitle(array $arrTitles) : array { return $this->internalGetByTitle($this->getQueryBuilder(), $arrTitles); }


    public function getByTitleComplete(array $arrTitles) : array
    {
        $arrResults = $this->internalGetByTitle($this->getQueryBuilderComplete(), $arrTitles);
        $this->arrEntityCache = array_merge($this->arrEntityCache, $arrResults);
        return $arrResults;
    }


    protected function internalGetByTitle(QueryBuilder $qb, array $arrTitles) : array
    {
        $arrTitlesToLoad = array_unique($arrTitles);
        $arrTitlesToLoad = array_filter($arrTitlesToLoad);

        if( empty($arrTitlesToLoad) ) {
            return [];
        }

        $arrEntitiesUnorderd =
            $qb
                ->andWhere(static::TITLE_FIELD . ' IN(:titles)')
                ->setParameter('titles', $arrTitlesToLoad)
                ->getQuery()->getResult();

        $arrEntities = [];
        foreach($arrTitlesToLoad as $title) {

            foreach($arrEntitiesUnorderd as $id => $entity) {

                if( $title == $entity->getTitle() ) {

                    $id = (string)$id;
                    $arrEntities[$id] = $entity;
                }
            }
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
