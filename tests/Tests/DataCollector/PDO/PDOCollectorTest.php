<?php

declare(strict_types=1);

namespace DebugBar\Tests\DataCollector\PDO;

use DebugBar\DataCollector\PDO\PDOCollector;
use DebugBar\DataCollector\PDO\TraceablePDO;
use DebugBar\DataCollector\PDO\TraceablePDOStatement;
use DebugBar\DataFormatter\DataFormatter;
use DebugBar\Tests\DebugBarTestCase;

class PDOCollectorTest extends DebugBarTestCase
{
    public function setUp(): void
    {
        if (!\extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('The pdo_sqlite extension is required.');
        }
    }

    public function testCollectsConnectionsAddedWithoutAName(): void
    {
        $first = new \PDO('sqlite::memory:');
        $second = new \PDO('sqlite::memory:');

        $collector = new PDOCollector();
        $collector->setDataFormatter(new DataFormatter());
        $collector->addConnection($first);
        $collector->addConnection($second);

        $connections = $collector->getConnections();
        $this->assertCount(2, $connections);

        $connections[array_key_first($connections)]->exec('SELECT 1');
        $connections[array_key_last($connections)]->exec('SELECT 2');

        $data = $collector->collect();

        $this->assertSame(2, $data['nb_statements']);
        $this->assertNotSame($data['statements'][0]['connection'], $data['statements'][1]['connection']);
    }

    public function testGroupsExecutionsOfTheSamePreparedStatement(): void
    {
        $pdo = new TraceablePDO(new \PDO('sqlite::memory:'));

        $first = $pdo->prepare('SELECT ?');
        $this->assertInstanceOf(TraceablePDOStatement::class, $first);
        $first->execute([1]);
        $first->execute([2]);
        $second = $pdo->prepare('SELECT ?');
        $this->assertInstanceOf(TraceablePDOStatement::class, $second);
        $second->execute([3]);

        $statements = $pdo->getExecutedStatements();

        $this->assertCount(3, $statements);
        $this->assertTrue($statements[0]->isPrepared());
        $this->assertSame($statements[0]->getPreparedId(), $statements[1]->getPreparedId());
        $this->assertNotSame($statements[0]->getPreparedId(), $statements[2]->getPreparedId());
    }
}
