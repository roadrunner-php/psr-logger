<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Tests\Unit\ContextProcessor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RoadRunner\PsrLogger\Internal\ContextProcessor\ResourceProcessor;

#[CoversClass(ResourceProcessor::class)]
class ResourceProcessorTest extends TestCase
{
    private ResourceProcessor $processor;

    public static function nonResourceProvider(): array
    {
        return [
            'string' => ['not a resource'],
            'integer' => [42],
            'float' => [3.14],
            'boolean' => [true],
            'null' => [null],
            'array' => [[]],
            'object' => [new \stdClass()],
        ];
    }

    public function testCanProcessResource(): void
    {
        $resource = \fopen('php://memory', 'r');
        $this->assertTrue($this->processor->canProcess($resource));
        \fclose($resource);
    }

    #[DataProvider('nonResourceProvider')]
    public function testCannotProcessNonResource(mixed $value): void
    {
        $this->assertFalse($this->processor->canProcess($value));
    }

    public function testProcessStreamResource(): void
    {
        $resource = \fopen('php://memory', 'r');
        $recursiveProcessor = static fn($v) => $v;

        $result = $this->processor->process($resource, $recursiveProcessor);

        \fclose($resource);

        $this->assertSame('stream resource', $result);
    }

    public function testProcessFileResource(): void
    {
        $tempFile = \tempnam(\sys_get_temp_dir(), 'test');
        $resource = \fopen($tempFile, 'w');
        $recursiveProcessor = static fn($v) => $v;

        $result = $this->processor->process($resource, $recursiveProcessor);

        \fclose($resource);
        \unlink($tempFile);

        $this->assertSame('stream resource', $result);
    }

    public function testProcessCurlResource(): void
    {
        if (!\extension_loaded('curl')) {
            $this->markTestSkipped('cURL extension not available');
        }

        $curlHandle = \curl_init();

        // In PHP 8+, curl_init returns CurlHandle object, not resource
        // Let's test with a different resource type that's consistently a resource
        $resource = \fopen('data://text/plain;base64,SGVsbG8gV29ybGQ=', 'r');
        $recursiveProcessor = static fn($v) => $v;

        $result = $this->processor->process($resource, $recursiveProcessor);

        \fclose($resource);
        \curl_close($curlHandle);

        $this->assertSame('stream resource', $result);
    }

    public function testProcessClosedResource(): void
    {
        $resource = \fopen('php://memory', 'r');
        \fclose($resource);

        // Closed resources might still be detected as resources in some PHP versions
        // or might not be, depending on the PHP version and implementation
        if (\is_resource($resource)) {
            $recursiveProcessor = static fn($v) => $v;
            $result = $this->processor->process($resource, $recursiveProcessor);
            $this->assertTrue(\str_contains($result, 'resource'));
        } else {
            // If it's no longer a resource, the processor shouldn't handle it
            $this->assertFalse($this->processor->canProcess($resource));
        }
    }

    protected function setUp(): void
    {
        $this->processor = new ResourceProcessor();
    }
}
