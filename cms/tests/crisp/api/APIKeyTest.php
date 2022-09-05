<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use crisp\api;
use crisp\core\Postgres;
use PDO;

final class APIKeyTest extends TestCase
{
    public function testCreateApiKey(): void
    {
        $DB = (new Postgres())->getDBConnector();

        $statement = $DB->query("INSERT INTO APIKeys (key) VALUES ('12345')");

        $this->assertTrue($statement->rowCount() > 0);
    }
}