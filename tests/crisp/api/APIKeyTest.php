<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use crisp\api;
use crisp\core\Postgres;

final class APIKeyTest extends TestCase
{
    public function testCreateApiKey(): void
    {
        $DB = (new Postgres())->getDBConnector();

        $statementInsert = $DB->query("INSERT INTO APIKeys (key) VALUES ('12345')");
        $this->assertTrue($statementInsert->rowCount() > 0);

        $statementDelete = $DB->query("DELETE FROM APIKeys WHERE key = '12345'");
        $this->assertTrue($statementDelete->rowCount() > 0);
    }

    /*
     * @depends testCreateApiKey
     */

    /*
    public function testDisableApiKey(): void
    {

        $class = new api\APIKey("12345");

        $this->assertTrue($class->disable());
    }
    */
}
