<?php namespace CodeIgniter\HTTP;

use Config\App;
use Config\Autoload;
use CodeIgniter\Config\Services;
use CodeIgniter\Validation\Validation;
use CodeIgniter\Router\RouteCollection;
use Tests\Support\Autoloader\MockFileLocator;
use Tests\Support\HTTP\MockIncomingRequest;

class RedirectResponseTest extends \CIUnitTestCase
{
	protected $routes;

	protected $request;

	protected $config;

	public function setUp()
	{
		parent::setUp();

		$_SERVER['REQUEST_METHOD'] = 'GET';

		$this->config = new App();
		$this->config->baseURL = 'http://example.com';

		$this->routes = new RouteCollection(new MockFileLocator(new Autoload()));
		Services::injectMock('routes', $this->routes);

		$this->request = new MockIncomingRequest($this->config, new URI('http://example.com'), null, new UserAgent());
		Services::injectMock('request', $this->request);
	}

	public function testRedirectToFullURI()
	{
		$response = new RedirectResponse(new App());

		$response = $response->to('http://example.com/foo');

		$this->assertTrue($response->hasHeader('Location'));
		$this->assertEquals('http://example.com/foo', $response->getHeaderLine('Location'));
	}

	public function testRedirectRelativeConvertsToFullURI()
	{
		$response = new RedirectResponse($this->config);

		$response = $response->to('/foo');

		$this->assertTrue($response->hasHeader('Location'));
		$this->assertEquals('http://example.com/foo', $response->getHeaderLine('Location'));
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testWithInput()
	{
		$_SESSION = [];
		$_GET = ['foo' => 'bar'];
		$_POST = ['bar' => 'baz'];

		$response = new RedirectResponse(new App());

		$returned = $response->withInput();

		$this->assertSame($response, $returned);
		$this->assertArrayHasKey('_ci_old_input', $_SESSION);
		$this->assertEquals('bar', $_SESSION['_ci_old_input']['get']['foo']);
		$this->assertEquals('baz', $_SESSION['_ci_old_input']['post']['bar']);
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testWithValidationErrors()
	{
		$_SESSION = [];

		$response = new RedirectResponse(new App());

		$validation = $this->createMock(Validation::class);
		$validation->method('getErrors')
		           ->willReturn(['foo' =>'bar']);

		Services::injectMock('validation', $validation);

		$response->withInput();

		$this->assertArrayHasKey('_ci_validation_errors', $_SESSION);
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testWith()
	{
		$_SESSION = [];

		$response = new RedirectResponse(new App());

		$returned = $response->with('foo', 'bar');

		$this->assertSame($response, $returned);
		$this->assertArrayHasKey('foo', $_SESSION);
	}
}
