<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\ProductApiException;
use App\Exception\ProductNotFoundException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use UnexpectedValueException;

final readonly class DummyJsonApiClient implements ProductApiClientInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string              $baseUrl,
    ) {
    }

    /** @return list<mixed> Decoded upstream payloads. */
    public function listProducts(): array
    {
        $data = $this->request('/products?limit=0&select=id,title,price,thumbnail,description,category');
        if (!$data instanceof \stdClass
            || !isset($data->products, $data->total, $data->skip)
            || !is_array($data->products) || !array_is_list($data->products)
            || !is_int($data->total) || $data->total !== count($data->products)
            || $data->skip !== 0
        ) {
            throw new ProductApiException(new UnexpectedValueException('Invalid DummyJSON product list.'));
        }

        return $data->products;
    }

    public function getProduct(int $id): mixed
    {
        return $this->request('/products/'.$id, true);
    }

    private function request(string $path, bool $single = false): mixed
    {
        try {
            $response = $this->httpClient->request('GET', rtrim($this->baseUrl, '/').$path, [
                'headers' => ['Accept' => 'application/json'],
                'timeout' => 5,
                'max_duration' => 10,
                'max_redirects' => 0,
            ]);
            $status = $response->getStatusCode();
            if ($single && $status === 404) {
                throw new ProductNotFoundException();
            }
            if ($status !== 200) {
                throw new UnexpectedValueException('DummyJSON HTTP '.$status);
            }

            $body = $response->getContent(false);
            return json_decode($body, false, 512, JSON_THROW_ON_ERROR);
        } catch (ExceptionInterface | \JsonException | UnexpectedValueException $exception) {
            throw new ProductApiException($exception);
        }
    }

}
