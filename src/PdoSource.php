<?php
/**
 * This file is part of the browscap-helper-source-pdo package.
 *
 * Copyright (c) 2016-2017, Thomas Mueller <mimmi20@live.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);
namespace BrowscapHelper\Source;

use Psr\Log\LoggerInterface;
use UaResult\Browser\Browser;
use UaResult\Device\Device;
use UaResult\Engine\Engine;
use UaResult\Os\Os;
use UaResult\Result\Result;
use Wurfl\Request\GenericRequestFactory;

/**
 * Class DirectorySource
 *
 * @author  Thomas Mueller <mimmi20@live.de>
 */
class PdoSource implements SourceInterface
{
    /**
     * @var \PDO
     */
    private $pdo = null;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger = null;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param \PDO                     $pdo
     */
    public function __construct(LoggerInterface $logger, \PDO $pdo)
    {
        $this->logger = $logger;
        $this->pdo    = $pdo;
    }

    /**
     * @param int $limit
     *
     * @return string[]
     */
    public function getUserAgents($limit = 0)
    {
        foreach ($this->getAgents($limit) as $agent) {
            yield $agent;
        }
    }

    /**
     * @return \UaResult\Result\Result[]
     */
    public function getTests()
    {
        foreach ($this->getAgents() as $agent) {
            $request  = (new GenericRequestFactory())->createRequestForUserAgent($agent);
            $browser  = new Browser(null);
            $device   = new Device(null, null);
            $platform = new Os(null, null);
            $engine   = new Engine(null);

            yield $agent => new Result($request, $device, $platform, $browser, $engine);
        }
    }

    /**
     * @param int $limit
     *
     * @return string[]
     */
    private function getAgents($limit = 0)
    {
        $sql = 'SELECT DISTINCT SQL_BIG_RESULT HIGH_PRIORITY `agent` FROM `agents` ORDER BY `lastTimeFound` DESC, `count` DESC, `idAgents` DESC';

        if ($limit) {
            $sql .= ' LIMIT ' . (int) $limit;
        }

        $driverOptions = [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY];

        /** @var \PDOStatement $stmt */
        $stmt = $this->pdo->prepare($sql, $driverOptions);
        $stmt->execute();

        while ($row = $stmt->fetch(\PDO::FETCH_OBJ)) {
            yield trim($row->agent);
        }
    }
}
