<?php

declare(strict_types=1);

namespace App\Http;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ApiResponseSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::VIEW => 'onView'];
    }

    public function onView(ViewEvent $event): void
    {
        $request = $event->getRequest();
        if (!$request->attributes->getBoolean('_api')) {
            return;
        }

        $status = $request->attributes->getInt('_api_status', Response::HTTP_OK);
        $response = $status === Response::HTTP_NO_CONTENT
            ? new Response(null, Response::HTTP_NO_CONTENT)
            : new JsonResponse(['data' => $event->getControllerResult()], $status);

        $event->setResponse($response);
    }
}
