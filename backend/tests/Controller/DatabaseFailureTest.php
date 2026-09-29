<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Service\DatabaseConnection;
use App\Service\DatabaseConnectionInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class DatabaseFailureTest extends WebTestCase
{
    public function testConnectionFailureIsNormalized(): void
    {
        $browser = self::createClient(['debug' => false]);
        self::getContainer()->set(
            DatabaseConnectionInterface::class,
            new DatabaseConnection('pgsql:host=127.0.0.1;port=1;dbname=missing;connect_timeout=1', 'test', 'test')
        );
        $browser->request('GET', '/api/v1/products/1/comments');
        self::assertResponseStatusCodeSame(503);
        $body = $browser->getResponse()->getContent();
        self::assertSame('DATABASE_UNAVAILABLE', json_decode($body, true)['error']['code']);
        self::assertStringNotContainsString('SQLSTATE', $body);
    }
}
