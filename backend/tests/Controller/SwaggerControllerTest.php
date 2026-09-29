<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Yaml\Yaml;

final class SwaggerControllerTest extends WebTestCase
{
    public function testUiUsesLocalAssetsAndContainsApiDocumentation(): void
    {
        $browser = self::createClient(['debug' => false]);
        $browser->request('GET', '/api/doc');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'text/html; charset=UTF-8');
        $html = $browser->getResponse()->getContent();
        self::assertStringContainsString('id="swagger-ui"', $html);
        self::assertStringContainsString('/bundles/nelmioapidoc/swagger-ui/swagger-ui-bundle.js', $html);
        self::assertStringContainsString('/api/v1/products', $html);
        self::assertStringNotContainsString('cdn.jsdelivr.net', $html);

        $configuration = json_decode($browser->getCrawler()->filterXPath('//script[@id="swagger-data"]')->text(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('3.0.3', $configuration['spec']['openapi']);
        self::assertArrayHasKey('/api/v1/comments', $configuration['spec']['paths']);
    }

    public function testJsonDocumentationIsNotWrappedInDataEnvelope(): void
    {
        $browser = self::createClient(['debug' => false]);
        $browser->request('GET', '/api/doc.json');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json');
        $spec = json_decode($browser->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('3.0.3', $spec['openapi']);
        self::assertArrayNotHasKey('data', $spec);
        self::assertSame('/', $spec['servers'][0]['url']);
        self::assertArrayHasKey('/api/v1/products', $spec['paths']);
        self::assertArrayHasKey('404', $spec['paths']['/api/v1/products/{id}']['get']['responses']);
        self::assertSame(
            '#/components/responses/ProductApiUnavailable',
            $spec['paths']['/api/v1/products']['get']['responses']['502']['$ref']
        );
        self::assertArrayNotHasKey('/api/doc', $spec['paths']);
        self::assertSame(
            '#/components/requestBodies/CreateCommentInput',
            $spec['paths']['/api/v1/comments']['post']['requestBody']['$ref']
        );
        self::assertArrayNotHasKey('parameters', $spec['paths']['/api/v1/comments']['post']);
        self::assertArrayNotHasKey('post', $spec['paths']['/api/v1/products/{id}/comments']);
        self::assertSame(['product_id', 'author', 'content'], $spec['components']['schemas']['CreateCommentInput']['required']);
        self::assertSame('integer', $spec['components']['schemas']['CreateCommentInput']['properties']['product_id']['type']);
    }

    public function testYamlExportPreservesTheDocumentedSchemas(): void
    {
        $browser = self::createClient(['debug' => false]);
        $browser->request('GET', '/api/doc.yaml');

        self::assertResponseIsSuccessful();
        $spec = Yaml::parse($browser->getResponse()->getContent());
        self::assertSame('3.0.3', $spec['openapi']);
        self::assertSame(
            ['id', 'title', 'price', 'image', 'description', 'category'],
            $spec['components']['schemas']['Product']['required']
        );

        $source = Yaml::parseFile(self::getContainer()->getParameter('kernel.project_dir').'/config/openapi.yaml');
        self::assertSame('3.0.3', $source['openapi']);
        self::assertArrayNotHasKey('nelmio_api_doc', $source);
        self::assertSame(array_keys($source['paths']), array_keys($spec['paths']));
        self::assertSame($source['info'], $spec['info']);
    }
}
