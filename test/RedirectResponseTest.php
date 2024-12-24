<?php

declare(strict_types=1);

namespace AxleusTest\Htmx;

use PHPUnit\Framework\TestCase;
use Axleus\Htmx\Response\RedirectResponse;

final class RedirectResponseTest extends TestCase
{
    public function testRedirectResponse()
    {
        $url = 'https://example.com';
        $response = new RedirectResponse($url);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals($url, $response->getUrl());
    }

    public function testRedirectResponseWithStatusCode()
    {
        $url = 'https://example.com';
        $statusCode = 302;
        $response = new RedirectResponse($url, $statusCode);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals($url, $response->getUrl());
        $this->assertEquals($statusCode, $response->getStatusCode());
    }
}
