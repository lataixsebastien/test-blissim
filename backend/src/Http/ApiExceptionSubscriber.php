<?php

declare(strict_types=1);

namespace App\Http;

use App\Exception\ProductApiException;
use App\Exception\ProductNotFoundException;
use App\Exception\CommentNotFoundException;
use App\Exception\DatabaseException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final readonly class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => ['onException', 10]];
    }

    public function onException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $headers = [];
        $details = [];
        if ($exception instanceof ProductNotFoundException) {
            [$status, $code, $message] = [404, 'PRODUCT_NOT_FOUND', $exception->getMessage()];
        } elseif ($exception instanceof ProductApiException) {
            [$status, $code, $message] = [502, 'PRODUCT_API_UNAVAILABLE', $exception->getMessage()];
        } elseif ($exception instanceof CommentNotFoundException) {
            [$status, $code, $message] = [404, 'COMMENT_NOT_FOUND', $exception->getMessage()];
        } elseif ($exception instanceof DatabaseException) {
            [$status, $code, $message] = [503, 'DATABASE_UNAVAILABLE', $exception->getMessage()];
        } elseif ($exception instanceof HttpExceptionInterface) {
            $status = $exception->getStatusCode();
            $headers = $exception->getHeaders();
            [$code, $message] = match ($status) {
                400 => ['INVALID_REQUEST', 'Le corps de la requête est invalide.'],
                404 => ['NOT_FOUND', 'Cette route est introuvable.'],
                405 => ['METHOD_NOT_ALLOWED', 'Cette méthode HTTP n’est pas autorisée.'],
                415 => ['UNSUPPORTED_MEDIA_TYPE', 'Envoyez les données au format JSON.'],
                422 => ['VALIDATION_ERROR', 'Les données envoyées sont invalides.'],
                default => ['HTTP_ERROR', 'La requête ne peut pas être traitée.'],
            };
            if ($exception->getPrevious() instanceof ValidationFailedException) {
                foreach ($exception->getPrevious()->getViolations() as $violation) {
                    $details[$violation->getPropertyPath()][] = $violation->getMessage();
                }
            }
        } else {
            [$status, $code, $message] = [500, 'INTERNAL_ERROR', 'Une erreur interne est survenue.'];
        }

        if ($status >= 500) {
            $request = $event->getRequest();
            $context = [
                'exception' => $exception,
                'method' => $request->getMethod(),
                'path' => $request->getPathInfo(),
                'route' => $request->attributes->get('_route'),
                'route_parameters' => array_intersect_key(
                    $request->attributes->get('_route_params', []),
                    ['id' => true, 'product_id' => true],
                ),
            ];
            if ($context['route'] === 'api_comments_create') {
                $payload = json_decode($request->getContent(), true);
                if (is_array($payload) && isset($payload['product_id']) && is_int($payload['product_id']) && $payload['product_id'] > 0) {
                    $context['product_id'] = $payload['product_id'];
                }
            }

            $this->logger->error($code, $context);
        }
        $error = ['code' => $code, 'message' => $message];
        if ($details !== []) {
            $error['details'] = $details;
        }
        $event->setResponse(new JsonResponse(['error' => $error], $status, $headers));
    }
}
