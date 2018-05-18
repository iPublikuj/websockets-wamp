<?php
/**
 * Storage.php
 *
 * @copyright      More in license.md
 * @license        https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:WebSocketsWAMP!
 * @subpackage     Topics
 * @since          1.0.0
 *
 * @date           14.02.17
 */

declare(strict_types = 1);

namespace IPub\WebSocketsWAMP\Topics;

use Nette;

use Psr\Log;

use IPub\WebSocketsWAMP\Entities;
use IPub\WebSocketsWAMP\Exceptions;
use IPub\WebSocketsWAMP\Topics\Drivers;

/**
 * Storage for manage all topics
 *
 * @package        iPublikuj:WebSocketsWAMP!
 * @subpackage     Topics
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
final class Storage implements IStorage
{
	/**
	 * Implement nette smart magic
	 */
	use Nette\SmartObject;

	/**
	 * @var Drivers\IDriver
	 */
	private $driver;

	/**
	 * @var int|NULL
	 */
	private $ttl;

	/**
	 * @var Log\LoggerInterface|Log\NullLogger|NULL
	 */
	private $logger;

	/**
	 * @param int|NULL $ttl
	 * @param Log\LoggerInterface|NULL $logger
	 */
	public function __construct(int $ttl = 0, Log\LoggerInterface $logger = NULL)
	{
		$this->ttl = $ttl;
		$this->logger = $logger === NULL ? new Log\NullLogger : $logger;
	}

	/**
	 * {@inheritdoc}
	 */
	public function setStorageDriver(Drivers\IDriver $driver) : void
	{
		$this->driver = $driver;
	}

	/**
	 * {@inheritdoc}
	 */
	public static function getStorageId(Entities\Topics\ITopic $topic) : string
	{
		return $topic->getId();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getTopic(string $identifier) : Entities\Topics\ITopic
	{
		try {
			$result = $this->driver->fetch($identifier);

		} catch (\Exception $ex) {
			throw new Exceptions\StorageException(sprintf('Driver %s failed', get_class($this)), $ex->getCode(), $ex);
		}

		$this->logger->debug('GET TOPIC ' . $identifier);

		if ($result === FALSE) {
			throw new Exceptions\TopicNotFoundException(sprintf('Topic %s not found', $identifier));
		}

		return $result;
	}

	/**
	 * {@inheritdoc}
	 */
	public function addTopic(string $identifier, Entities\Topics\ITopic $topic) : void
	{
		$context = [
			'topic' => $identifier,
		];

		$this->logger->debug(sprintf('INSERT TOPIC ' . $identifier), $context);

		try {
			$result = $this->driver->save($identifier, $topic, $this->ttl);

		} catch (\Exception $ex) {
			throw new Exceptions\StorageException(sprintf('Driver %s failed', get_class($this)), $ex->getCode(), $ex);
		}

		if ($result === FALSE) {
			throw new Exceptions\StorageException('Unable add topic');
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function hasTopic(string $identifier) : bool
	{
		try {
			$result = $this->driver->contains($identifier);

		} catch (\Exception $ex) {
			throw new Exceptions\StorageException(sprintf('Driver %s failed', get_class($this)), $ex->getCode(), $ex);
		}

		return $result;
	}

	/**
	 * {@inheritdoc}
	 */
	public function removeTopic(string $identifier) : bool
	{
		$this->logger->debug('REMOVE TOPIC ' . $identifier);

		try {
			$result = $this->driver->delete($identifier);

		} catch (\Exception $ex) {
			throw new Exceptions\StorageException(sprintf('Driver %s failed', get_class($this)), $ex->getCode(), $ex);
		}

		return $result;
	}

	/**
	 * @return Entities\Topics\ITopic[]|\ArrayIterator
	 */
	public function getIterator() : \ArrayIterator
	{
		return new \ArrayIterator($this->driver->fetchAll());
	}
}
