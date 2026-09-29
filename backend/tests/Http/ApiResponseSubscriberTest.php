<?php

declare(strict_types=1);

namespace App\Tests\Http;

use App\Http\ApiResponseSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class ApiResponseSubscriberTest extends TestCase
{
    public function testCreationStatusAndDataEnvelope(): void
    {
        $event = $this->event(['_api' => true, '_api_status' => Response::HTTP_CREATED], (object) ['id' => 1]);
        (new ApiResponseSubscriber())->onView($event);

        self::assertSame(Response::HTTP_CREATED, $event->getResponse()->getStatusCode());
        self::assertSame(['data' => ['id' => 1]], json_decode($event->getResponse()->getContent(), true));
    }

    public function testNoContentHasNoJsonBody(): void
    {
        $event = $this->event(['_api' => true, '_api_status' => Response::HTTP_NO_CONTENT], null);
        (new ApiResponseSubscriber())->onView($event);

        self::assertSame(Response::HTTP_NO_CONTENT, $event->getResponse()->getStatusCode());
        self::assertSame('', $event->getResponse()->getContent());
    }

    public function testNonApiResultIsLeftUntouched(): void
    {
        $event = $this->event([], ['hello']);
        (new ApiResponseSubscriber())->onView($event);

        self::assertFalse($event->hasResponse());
    }

    private function event(array $attributes, mixed $result): ViewEvent
    {
        $request = Request::create('/test');
        $request->attributes->add($attributes);

        return new ViewEvent(
            $this->createStub(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $result
        );
    }
}
