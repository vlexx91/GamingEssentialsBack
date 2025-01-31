<?php

namespace App\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;

class DoctrineRandomFunction
{
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->addRandomFunction();
    }

    public function addRandomFunction(): void
    {
        // Asegúrate de que es la plataforma PostgreSQL
        $platform = $this->connection->getDatabasePlatform();

        if ($platform instanceof PostgreSQLPlatform) {
            // Registra la función RANDOM() de PostgreSQL
            $this->connection->executeStatement("CREATE OR REPLACE FUNCTION random() RETURNS float AS $$ SELECT random(); $$ LANGUAGE sql IMMUTABLE;");
        }
    }
}