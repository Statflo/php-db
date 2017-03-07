<?php

namespace Statflo\DB\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Statflo\DB\Entity\Entity;
use Statflo\DB\Entity\Timestampable;

abstract class AbstractRepository
{
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    abstract public function getEntityName();

    abstract public function getTableName();

    /**
     * @param Criteria[]
     * @param string
     *
     * @return QueryBuilder
     */
    public function getQuery(array $criteria = [], $operator = null)
    {
        $properties   = $this->getProperties();
        $queryBuilder = $this->connection->createQueryBuilder();
        $operator     = $operator ?: Operator::_AND;
        $counter      = 0;
        $x = [];

        $queryBuilder
            ->select($properties)
            ->from($this->getRealTableName(), null)
        ;

        foreach ($criteria as $c) {
            foreach ($c->get() as $k => $v) {
                $queryBuilder
                    ->{$operator}($k)
                    ->setParameter($counter++, $v)
                ;
                $x[$k] = $v;
            }
        }

        return $queryBuilder;
    }

    public function findAll(array $criteria = [], $operator = null, $limit = null)
    {
        $entries = [];
        $stmt    = $this->getQuery($criteria, $operator);

        if (is_numeric($limit)) {
            $stmt->setMaxResults($limit);
        }

        $x = $stmt->execute();

        while ($user = $x->fetchObject($this->getEntityName())) {
            $entries[] = $user;
        }

        return new FilterableCollection($entries);
    }

    public function findOne(array $criteria = [], $operator = null)
    {
        $entries = [];
        $stmt    = $this->getQuery($criteria, $operator)->setMaxResults(1);
        $x       = $stmt->execute();

        return $x->fetchObject($this->getEntityName());
    }

    public function get($id, array $criteria = [])
    {
        $stmt = $this->getQuery([new Criteria("id = ?", $id)])->setMaxResults(1);
        $x    = $stmt->execute();

        return $x->fetchObject($this->getEntityName());
    }

    public function save(Entity $entity)
    {

        $parameters     = [];
        $parametersType = [];
        $properties     = $this->getProperties();
        $timestampable  = in_array(Timestampable::class, class_uses($entity));
        $properties     = array_filter($properties, function($property){
            return $property !== "id";
        });

        if ($timestampable) {
            $entity->onUpdate();

            if (!$entity->getId()) {
                $entity->onCreate();
            }
        }

        array_walk(
            $properties,
            function($data) use($entity, &$parameters, &$parametersType) {
                $reflectedObject = new \ReflectionObject($entity);
                $property        = $reflectedObject->getProperty($data);

                $property->setAccessible(true);

                $parameters[$data] = $property->getValue($entity);

                $type = \PDO::PARAM_STR;

                switch (gettype($parameters[$data])) {
                    case "boolean":
                        $parametersType[] = \PDO::PARAM_BOOL;
                        break;
                    case "NULL":
                        $parametersType[] = \PDO::PARAM_NULL;
                        break;
                    case "integer":
                        $parametersType[] = \PDO::PARAM_INT;
                        break;
                    case "object":
                        if ($parameters[$data] instanceof \DateTime) {
                            $parametersType[] = 'datetime';
                            break;
                        }
                    default:
                        $parametersType[] = \PDO::PARAM_STR;
                }
            }
        );

        if ($entity->getId()) {
            return $this->connection->update($this->getRealTableName(), $parameters, ['id' => $entity->getId()], $parametersType);
        }

        return $this->connection->insert($this->getRealTableName(), $parameters, $parametersType);
    }

    private function getReflectedEntity()
    {
        return new \ReflectionClass($this->getEntityName());
    }

    private function getProperties()
    {
        return array_map(function($p){ return $p->getName();}, $this->getReflectedEntity()->getProperties());
    }

    private function getRealTableName()
    {
        return $this
            ->connection
            ->quoteIdentifier(
                sprintf(
                    '%s.%s',
                    $this
                        ->connection
                        ->getDatabase(),
                    $this->getTableName()
                )
            )
        ;
    }
}