<?php

namespace App\Listeners;


use App\Model\Entity\Person;
use App\Model\Repositories\PersonsRepository;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\PersistentCollection;
use Kdyby\Events\Subscriber;
use Tracy\Debugger;


class AuditLogListener implements Subscriber
{
	/**
	 * @var \Nette\Security\User
	 */
	private $user;

	/**
	 * @var PersonsRepository
	 */
	private $personsRepository;


	/**
	 * AuditLogListener constructor.
	 * @param \Nette\Security\User $user
	 * @param PersonsRepository $personsRepository
	 */
	public function __construct(\Nette\Security\User $user, PersonsRepository $personsRepository)
	{
		$this->user = $user;
		$this->personsRepository = $personsRepository;
	}


	/**
	 * Returns an array of events this subscriber wants to listen to.
	 *
	 * @return string[]
	 */
	public function getSubscribedEvents()
	{
		return [
			Events::onFlush
		];
	}

	public function onFlush(OnFlushEventArgs $eventArgs)
	{
		$em = $eventArgs->getEntityManager();
		$uow = $em->getUnitOfWork();

		/** @var Person $user */
		$user = $this->user->isLoggedIn()
					? $this->personsRepository->find($this->user->getId())
					: null;

		/* @var $resourceClass \Doctrine\ORM\Mapping\ClassMetadata */
//		$resourceClass = $em->getClassMetadata(AuditLog::class);

		foreach ($uow->getScheduledEntityUpdates() as $entity)
		{
			$entityId = $uow->getSingleIdentifierValue($entity);
			$changeSet = $uow->getEntityChangeSet($entity);

			$changes = $this->getChanges($changeSet);

			if (!$changes)
			{
				continue;
			}

			$description = sprintf(
				'Změna v %s#%s (%s) od %s v %s',
				get_class($entity),
				$entityId,
				implode(', ', $changes),
				$user ? $user->getFullname() : 'nepřihlášený',
				date('j.n.y H:i:s')
			);

			Debugger::log($description, 'audit');



//			$auditLog = new AuditLog(AuditLog::UPDATE);
//			$auditLog->setEntityClass(get_class($entity));
//			$auditLog->setEntityId($entityId);
//			$auditLog->setChangeSet($changeSet);
//			$auditLog->setDescription($description);
//			$auditLog->setOccurredAt(new \DateTime('now'));
//			$auditLog->setUser($user);

//			$em->persist($auditLog);
//			// Necessary instead of $em->flush() because we're already in flush process
//			$uow->computeChangeSet($resourceClass, $auditLog);
		}

		foreach ($uow->getScheduledEntityInsertions() as $entity)
		{
			$changeSet = $uow->getEntityChangeSet($entity);

			$changes = $this->getChanges($changeSet);

			if (!$changes)
			{
				continue;
			}

			$description = sprintf(
				'Vytvořeno %s (%s) od %s v %s',
				get_class($entity),
				implode(', ', $changes),
				$user ? $user->getFullname() : 'nepřihlášený',
				date('j.n.y H:i:s')
			);

			Debugger::log($description, 'audit');
		}


		foreach ($uow->getScheduledEntityDeletions() as $entity)
		{
			$entityId = $uow->getSingleIdentifierValue($entity);
			$changeSet = $uow->getEntityChangeSet($entity);
			$changes = $this->getChanges($changeSet);

			if (!$changes)
			{
				continue;
			}

			$description = sprintf(
				'Smazáno %s#%s (%s) od %s v %s',
				get_class($entity),
				$entityId,
				implode(', ', $changes),
				$user ? $user->getFullname() : 'nepřihlášený',
				date('j.n.y H:i:s')
			);

			Debugger::log($description, 'audit');
		}
//

		/** @var PersistentCollection $col */
		foreach ($uow->getScheduledCollectionDeletions() as $col) {
			$col->getInsertDiff();
			$col->getDeleteDiff();
		}
//
		/** @var PersistentCollection $col */
		foreach ($uow->getScheduledCollectionUpdates() as $col) {


			$fieldname = $col->getMapping()['fieldName'];
			$owner = $col->getOwner();
			$ownerId = $uow->getSingleIdentifierValue($owner);

			$insert = [];
			foreach ($col->getInsertDiff() as $entity)
			{
				$insert[] = $this->stringify($entity);
			}

			$delete = [];
			foreach ($col->getDeleteDiff() as $entity)
			{
				$delete[] = $this->stringify($entity);
			}

			if (empty($insert) && empty($delete))
			{
				continue;
			}

			$changes = sprintf('%s: %s%s',
				$fieldname,
				$insert ? ('+' . implode(', +', $insert)) : '',
				$delete ? ('-' . implode(', -', $delete)) : ''
			);

			$description = sprintf(
				'Změna v %s#%s (%s) od %s v %s',
				get_class($owner),
				$ownerId,
				$changes,
				$user ? $user->getFullname() : 'nepřihlášený',
				date('j.n.y H:i:s')
			);

			Debugger::log($description, 'audit');
		}
	}

	/**
	 * @param $value
	 * @return string
	 */
	public function stringify($value)
	{
		if (is_scalar($value))
		{
			return (string) $value;
		}
		elseif ($value instanceof \DateTime)
		{
			return $value->format('j.n.Y H:i:s');
		}
		elseif (is_object($value) && preg_match("~App.Model.Entity.(.+)~", get_class($value), $match))
		{
			return sprintf('%s#%s', $match[1], $value->getId());
		}
		elseif (is_object($value) && method_exists($value, '__toString'))
		{
			return (string) $value;
		}
		elseif (is_array($value))
		{
			return json_encode($value);
		}
		elseif (is_null($value))
		{
			return 'NULL';
		}

		return serialize($value);
	}

	/**
	 * @param $changeSet
	 * @return array
	 */
	private function getChanges($changeSet)
	{
		$description = [];
		foreach ($changeSet as $key => $values) {
			list($oldValue, $newValue) = $values;

			$oldValue = $this->stringify($oldValue);
			$newValue = $this->stringify($newValue);

			if ($oldValue !== $newValue)
			{
				$description[] = sprintf('%s: %s -> %s', $key, $oldValue, $newValue);
			}
		}
		return $description;
	}


}