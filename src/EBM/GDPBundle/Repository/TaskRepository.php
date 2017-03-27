<?php

namespace EBM\GDPBundle\Repository;

/**
 * TaskRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TaskRepository extends \Doctrine\ORM\EntityRepository
{
    public function findNonArchivedTasks() {
        return $this
            ->createQueryBuilder('task')
            ->where('task.status <> :status')
            ->setParameter('status',"ARCHIVED")
            ->getQuery()
            ->execute();
    }

    public function findArchivedTasks() {
        return $this
            ->createQueryBuilder('task')
            ->where('task.status = :status')
            ->setParameter('status',"ARCHIVED")
            ->getQuery()
            ->execute();
    }
}
