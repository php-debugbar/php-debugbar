<?php

declare(strict_types=1);

/*
 * This file is part of the DebugBar package.
 *
 * (c) 2013 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DebugBar\Storage;

use PDO;

/**
 * Stores collected data into a SQLite database file
 *
 * Extends PdoStorage with SQLite-specific features like automatic table creation
 * and database optimization
 */
class SqliteStorage extends PdoStorage
{
    /**
     * @param string $filepath  Path to the SQLite database file
     * @param string $tableName Name of the table to store data
     */
    public function __construct(
        string $filepath,
        string $tableName = 'phpdebugbar'
    ) {
        // Create directory if it doesn't exist
        $dirname = dirname($filepath);
        if (!is_dir($dirname)) {
            if (!@mkdir($dirname, 0o755, true) && !is_dir($dirname)) {
                throw new \RuntimeException("Cannot create directory: $dirname");
            }
        }

        // Create or open SQLite database
        $pdo = new PDO('sqlite:' . $filepath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        parent::__construct($pdo, $tableName);

        $this->createTable();
    }

    public function clear(): void
    {
        parent::clear();

        $this->optimize();
    }

    public function prune(int $hours = 24): void
    {
        parent::prune($hours);

        $this->optimize();
    }

    /**
     * Optimizes the database (runs VACUUM)
     *
     * This can be called periodically to reclaim space from deleted records
     */
    public function optimize(): void
    {
        $this->getPdo()->exec('VACUUM');
    }
}
