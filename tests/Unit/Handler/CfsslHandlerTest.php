<?php

namespace OCA\Libresign\Tests\Unit\Handler;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use OCA\Libresign\Handler\CfsslHandler;
use Psr\Http\Message\ResponseInterface;

/**
 * @internal
 */
final class CfsslHandlerTest extends \OCA\Libresign\Tests\Unit\TestCase {
	public function testGenerateCertificateWithInvalidHost() {
		$class = new CfsslHandler();
		$this->expectErrorMessageMatches('/Could not resolve host/');
		$class->generateCertificate();
	}

	public function testSetNonExististingProperty() {
		$class = new CfsslHandler();
		$this->expectErrorMessageMatches('/Cannot set non existing property/');
		$class->setFoo();
	}

	public function testCallInvalidMethod() {
		$class = new CfsslHandler();
		$this->expectErrorMessageMatches('/Cannot set non existing property/');
		$class->fooBar();
	}

	public function testGenerateCertificateWhenCfsslReturningInvalidData() {
		$class = new CfsslHandler();
		$this->expectErrorMessage('Error while generating certificate keys!');
		$this->expectExceptionCode(500);
		$response = $this->createMock(ResponseInterface::class);
		$response->method('getBody')->willReturn(json_encode([
			'success' => false
		]));
		$client = $this->createMock(ClientInterface::class);
		$client->expects($this->once())
			->method('request')
			->willReturn($response);
		$class->setClient($client);
		$class->generateCertificate();
	}

	public function testGenerateCertificateWithError() {
		$class = new CfsslHandler();
		$this->expectErrorMessageMatches('/Could not resolve host/');
		$class->generateCertificate();
	}

	public function testGenerateCertificateWithUnexpectedError() {
		$class = new CfsslHandler();
		$this->expectExceptionCode(500);
		$client = $this->createMock(ClientInterface::class);
		$client->expects($this->once())
			->method('request')
			->willThrowException($this->createMock(ConnectException::class));
		$class->setClient($client);
		$class->generateCertificate();
	}

	public function testGenerateCertificateWithInvalidCert() {
		$class = new CfsslHandler();
		$this->expectExceptionMessage('Error while creating certificate file');
		$this->expectExceptionCode(500);
		$response = $this->createMock(ResponseInterface::class);
		$cert = [
			'certificate' => null,
			'private_key' => null
		];
		$response->method('getBody')->willReturn(json_encode([
			'success' => 'success',
			'result' => $cert
		]));
		$client = $this->createMock(ClientInterface::class);
		$client->expects($this->once())
			->method('request')
			->willReturn($response);
		$class->setClient($client);
		$class->generateCertificate();
	}

	public function testGenerateCertificateWithValidCert() {
		$class = new CfsslHandler();
		$response = $this->createMock(ResponseInterface::class);
		$cert = file_get_contents(__DIR__ . '/mock/cert.json');
		$response->method('getBody')->willReturn(json_encode([
			'success' => 'success',
			'result' => json_decode($cert, true)
		]));
		$client = $this->createMock(ClientInterface::class);
		$client->expects($this->once())
			->method('request')
			->willReturn($response);
		$class->setClient($client);
		$class->setPassword('password');
		$pkcs12 = $class->generateCertificate();
		openssl_pkcs12_read($pkcs12, $actual, 'password');
		$this->assertArrayHasKey('cert', $actual);
		$this->assertArrayHasKey('pkey', $actual);
	}

	public function testHealthWithError() {
		$class = new CfsslHandler();
		$this->expectErrorMessage('invalid url');
		$exception = $this->createMock(ConnectException::class);
		$exception->method('getHandlerContext')->willReturn(['error' => 'invalid url']);
		$client = $this->createMock(ClientInterface::class);
		$client->expects($this->once())
			->method('request')
			->willThrowException($exception);
		$class->setClient($client);
		$class->health('invalid_url');
	}

	public function testHealthWithUnexpectedError() {
		$class = new CfsslHandler();
		$this->expectExceptionCode(500);
		$client = $this->createMock(ClientInterface::class);
		$client->expects($this->once())
			->method('request')
			->willThrowException($this->createMock(ConnectException::class));
		$class->setClient($client);
		$class->health('invalid_url');
	}

	public function testHealthWithoutSuccess() {
		$class = new CfsslHandler();
		$this->expectExceptionMessage('Error while check cfssl API health!');
		$response = $this->createMock(ResponseInterface::class);
		$response->method('getBody')->willReturn(json_encode([
			'success' => false,
			'result' => true
		]));
		$client = $this->createMock(ClientInterface::class);
		$client->expects($this->once())
			->method('request')
			->willReturn($response);
		$class->setClient($client);
		$class->health('http://cfssl.coop');
	}

	public function testHealthWithSuccess() {
		$class = new CfsslHandler();
		$response = $this->createMock(ResponseInterface::class);
		$response->method('getBody')->willReturn(json_encode([
			'success' => 'success',
			'result' => ['healthy' => true]
		]));
		$client = $this->createMock(ClientInterface::class);
		$client->expects($this->once())
			->method('request')
			->willReturn($response);
		$class->setClient($client);
		$actual = $class->health('http://cfssl.coop');
		$this->assertNotEmpty($actual);
	}
}
