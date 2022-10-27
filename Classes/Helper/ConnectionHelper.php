<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/mysqlreport.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Mysqlreport\Helper;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Exception;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Helper to wrap the executeQuery method.
 * Needed to temporarily deactivate the SQL logger
 */
class ConnectionHelper
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var SqlLoggerHelper
     */
    private $sqlLoggerHelper;

    /**
     * Do not add any parameters to this constructor!
     * This class was called so early that you can not flush cache over BE and Installtool.
     */
    public function __construct()
    {
        $this->connection = $this->getConnection();

        $this->sqlLoggerHelper = GeneralUtility::makeInstance(SqlLoggerHelper::class);
        $this->sqlLoggerHelper->setConnectionConfiguration($this->connection->getConfiguration());
    }

    /**
     * Executes a query which will not be logged by our SQL logger
     */
    public function executeQuery(string $query): ?Statement
    {
        if (!$this->isConnectionAvailable()) {
            return null;
        }

        $currentSqlLogger = $this->sqlLoggerHelper->getCurrentSqlLogger();
        $this->sqlLoggerHelper->deactivateSqlLogger();

        try {
            $statement = $this->connection->executeQuery($query);
        } catch (Exception $exception) {
            $statement = null;
        }

        $this->sqlLoggerHelper->activateSqlLogger($currentSqlLogger);

        return $statement;
    }

    /**
     * Executes a bulk insert which will not be logged by our SQL logger
     */
    public function bulkInsert(string $tableName, array $data, array $columns = [], array $types = []): int
    {
        if (!$this->isConnectionAvailable()) {
            return 0;
        }

        $currentSqlLogger = $this->sqlLoggerHelper->getCurrentSqlLogger();
        $this->sqlLoggerHelper->deactivateSqlLogger();

        $affectedRows = $this->connection->bulkInsert($tableName, $data, $columns, $types);

        $this->sqlLoggerHelper->activateSqlLogger($currentSqlLogger);

        return $affectedRows;
    }

    public function quote(string $value): string
    {
        if (!$this->isConnectionAvailable()) {
            return '';
        }

        return $this->connection->quote($value);
    }

    public function getConnectionConfiguration(): ?Configuration
    {
        if (!$this->isConnectionAvailable()) {
            return null;
        }

        return $this->connection->getConfiguration();
    }

    public function isConnectionAvailable(): bool
    {
        return $this->connection instanceof Connection;
    }

    public function getQueryBuilderForTable(string $table): QueryBuilder
    {
        return $this->getConnectionPool()->getQueryBuilderForTable($table);
    }

    public function executeQueryBuilder(QueryBuilder $queryBuilder): Statement
    {
        return $queryBuilder->execute();
    }

    private function getConnection(): ?Connection
    {
        try {
            return $this->getConnectionPool()->getConnectionByName(
                ConnectionPool::DEFAULT_CONNECTION_NAME
            );
        } catch (\UnexpectedValueException $unexpectedValueException) {
            // Should never be thrown, as a hard-coded name was added as parameter
        } catch (\RuntimeException $runtimeException) {
            // Default database of TYPO3 is not configured
        } catch (Exception $exception) {
            // Something breaks in DriverManager of Doctrine DBAL
        }

        return null;
    }

    private function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}