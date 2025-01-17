<?php

namespace OCA\Libresign\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * Class FileUserMapper
 *
 * @package OCA\Libresign\DB
 *
 * @codeCoverageIgnore
 * @method FileUser insert(FileUser $entity)
 * @method FileUser update(FileUser $entity)
 * @method FileUser insertOrUpdate(FileUser $entity)
 * @method FileUser delete(FileUser $entity)
 */
class FileUserMapper extends QBMapper {
	/** @var FileUser[][] */
	private $signers = [];

	/**
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'libresign_file_user');
	}

	/**
	 * Returns all users who have not signed
	 *
	 * @return \OCP\AppFramework\Db\Entity[] all fetched entities
	 *
	 * @psalm-return array<\OCP\AppFramework\Db\Entity>
	 */
	public function findUnsigned(): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->isNull('signed')
			);

		return $this->findEntities($qb);
	}

	/**
	 * Get file user by UUID
	 *
	 * @param string $uuid
	 * @return FileUser
	 */
	public function getByUuid(string $uuid): FileUser {
		if (!isset($this->signers['fileUserUuid'][$uuid])) {
			$qb = $this->db->getQueryBuilder();

			$qb->select('*')
				->from($this->getTableName())
				->where(
					$qb->expr()->eq('uuid', $qb->createNamedParameter($uuid, IQueryBuilder::PARAM_STR))
				);

			$this->signers['fileUserUuid'][$uuid] = $this->findEntity($qb);
		}
		return $this->signers['fileUserUuid'][$uuid];
	}

	public function getByEmailAndFileId(string $email, int $fileId): \OCP\AppFramework\Db\Entity {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('email', $qb->createNamedParameter($email, IQueryBuilder::PARAM_STR))
			)
			->andWhere(
				$qb->expr()->eq('file_id', $qb->createNamedParameter($fileId, IQueryBuilder::PARAM_INT))
			);

		return $this->findEntity($qb);
	}

	/**
	 * Get all signers by fileId
	 *
	 * @param int $fileId
	 *
	 * @return FileUser|\OCP\AppFramework\Db\Entity[]
	 *
	 * @psalm-return FileUser|array<int, \OCP\AppFramework\Db\Entity>
	 */
	public function getByFileId(int $fileId) {
		if (!isset($this->signers['fileId'][$fileId])) {
			$qb = $this->db->getQueryBuilder();

			$qb->select('*')
				->from($this->getTableName())
				->where(
					$qb->expr()->eq('file_id', $qb->createNamedParameter($fileId, IQueryBuilder::PARAM_INT))
				);
			$signers = $this->findEntities($qb);
			$this->signers['fileId'][$fileId] = [];
			foreach ($signers as $signer) {
				$this->signers['fileId'][$fileId][$signer->getId()] = $signer;
			}
		}
		return $this->signers['fileId'][$fileId];
	}

	public function getById(int $fileUserId): FileUser {
		if (!isset($this->signers['id'][$fileUserId])) {
			$qb = $this->db->getQueryBuilder();

			$qb->select('*')
				->from($this->getTableName())
				->where(
					$qb->expr()->eq('id', $qb->createNamedParameter($fileUserId, IQueryBuilder::PARAM_INT))
				);
			$this->signers['id'][$fileUserId] = $this->findEntity($qb);
		}
		return $this->signers['id'][$fileUserId];
	}

	/**
	 * Get all signers by multiple fileId
	 *
	 * @param array $fileId
	 * @return FileUser[]
	 */
	public function getByMultipleFileId(array $fileId) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->in('file_id', $qb->createNamedParameter($fileId, IQueryBuilder::PARAM_INT_ARRAY))
			);

		return $this->findEntities($qb);
	}

	/**
	 * Get all signers by fileId
	 *
	 *
	 * @param int $nodeId
	 *
	 * @return FileUser[]
	 */
	public function getByNodeId(int $nodeId) {
		if (!isset($this->signers['nodeId'][$nodeId])) {
			$qb = $this->db->getQueryBuilder();
	
			$qb->select('fu.*')
				->from($this->getTableName(), 'fu')
				->join('fu', 'libresign_file', 'f', 'fu.file_id = f.id')
				->where(
					$qb->expr()->eq('f.node_id', $qb->createNamedParameter($nodeId, IQueryBuilder::PARAM_STR))
				);
	
			$this->signers['nodeId'][$nodeId] = $this->findEntities($qb);
		}
		return $this->signers['nodeId'][$nodeId];
	}

	/**
	 * Get all signers by File Uuid
	 *
	 * @param string $nodeId
	 * @return FileUser[]
	 */
	public function getByFileUuid(string $uuid) {
		if (!isset($this->signers['fileUuid'][$uuid])) {
			$qb = $this->db->getQueryBuilder();

			$qb->select('fu.*')
				->from($this->getTableName(), 'fu')
				->join('fu', 'libresign_file', 'f', 'fu.file_id = f.id')
				->where(
					$qb->expr()->eq('f.uuid', $qb->createNamedParameter($uuid, IQueryBuilder::PARAM_STR))
				);

			$this->signers['fileUuid'][$uuid] = $this->findEntities($qb);
		}
		return $this->signers['fileUuid'][$uuid];
	}

	public function getByUuidAndUserId(string $uuid, string $userId): FileUser {
		if (!isset($this->signers['fileUserUuid'][$uuid])) {
			$qb = $this->db->getQueryBuilder();

			$qb->select('*')
				->from($this->getTableName())
				->where(
					$qb->expr()->eq('uuid', $qb->createNamedParameter($uuid, IQueryBuilder::PARAM_STR))
				)
				->andWhere(
					$qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR))
				);

			$this->signers['fileUserUuid'][$uuid] = $this->findEntity($qb);
		}
		return $this->signers['fileUserUuid'][$uuid];
	}

	public function getByFileIdAndUserId(string $file_id, string $userId): FileUser {
		$qb = $this->db->getQueryBuilder();

		$qb->select('fu.*')
			->from($this->getTableName(), 'fu')
			->join('fu', 'libresign_file', 'f', 'fu.file_id = f.id')
			->where(
				$qb->expr()->eq('f.node_id', $qb->createNamedParameter($file_id, IQueryBuilder::PARAM_STR))
			)
			->andWhere(
				$qb->expr()->eq('fu.user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR))
			);

		return $this->findEntity($qb);
	}

	public function getByFileIdAndEmail(string $file_id, string $email): FileUser {
		$qb = $this->db->getQueryBuilder();

		$qb->select('fu.*')
			->from($this->getTableName(), 'fu')
			->join('fu', 'libresign_file', 'f', 'fu.file_id = f.id')
			->where(
				$qb->expr()->eq('f.node_id', $qb->createNamedParameter($file_id, IQueryBuilder::PARAM_STR))
			)
			->andWhere(
				$qb->expr()->eq('fu.email', $qb->createNamedParameter($email, IQueryBuilder::PARAM_STR))
			);

		return $this->findEntity($qb);
	}

	public function getByFileIdAndFileUserId(int $fileId, int $fileUserId): FileUser {
		if (!isset($this->signers['fileId'][$fileId][$fileUserId])) {
			$qb = $this->db->getQueryBuilder();
	
			$qb->select('fu.*')
				->from($this->getTableName(), 'fu')
				->join('fu', 'libresign_file', 'f', 'fu.file_id = f.id')
				->where(
					$qb->expr()->eq('f.node_id', $qb->createNamedParameter($fileId, IQueryBuilder::PARAM_STR))
				)
				->andWhere(
					$qb->expr()->eq('fu.id', $qb->createNamedParameter($fileUserId, IQueryBuilder::PARAM_STR))
				);
	
			$this->signers['fileId'][$fileId][$fileUserId] = $this->findEntity($qb);
		}
		return $this->signers['fileId'][$fileId][$fileUserId];
	}
}
