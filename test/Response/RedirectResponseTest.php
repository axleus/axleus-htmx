<?php

declare(strict_types=1);

namespace AxleusTest\Htmx\Response;

use Axleus\Htmx\ResponseHeaders as Header;
use Axleus\Htmx\Response\RedirectResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass((RedirectResponse::class))]
final class RedirectResponseTest extends TestCase
{
    private string $uri = 'https://example.com';

    public function testRedirectResponse()
    {
        $response = new RedirectResponse($this->uri);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals($this->uri, $response->getHeader(Header::HX_Redirect->value)[0]);
    }

    public function testRedirectResponseWithStatusCode()
    {
        $url = 'https://example.com';
        $statusCode = 200;
        $response = new RedirectResponse($url, $statusCode);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals($url, $response->getHeader(Header::HX_Redirect->value)[0]);
        $this->assertEquals($statusCode, $response->getStatusCode());
    }
}
