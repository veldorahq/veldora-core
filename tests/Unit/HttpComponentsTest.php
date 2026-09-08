<?php

declare(strict_types=1);

namespace Veldora\Framework\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Veldora\Framework\Foundation\Application;
use Veldora\Framework\Http\JsonResponse;
use Veldora\Framework\Http\RedirectResponse;
use Veldora\Framework\Http\Response;
use Veldora\Framework\Http\ResponseFactory;
use Veldora\Framework\Http\Session as HttpSession;
use Veldora\Framework\Http\UploadedFile;

class HttpComponentsTest extends TestCase
{
    protected Application $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = new Application(dirname(__DIR__, 2));
    }

    public function test_http_session_operations(): void
    {
        $session = new HttpSession();
        $_SESSION = [];

        $session->put('user.name', 'Alice');
        $session->put('user.role', 'admin');

        $this->assertTrue($session->has('user.name'));
        $this->assertSame('Alice', $session->get('user.name'));
        $this->assertSame('admin', $session->get('user.role'));

        // Pull
        $role = $session->pull('user.role');
        $this->assertSame('admin', $role);
        $this->assertNull($session->get('user.role'));

        // Flash
        $session->flash('status', 'Profile updated');
        // Flash in 'next' request initially
        $this->assertIsArray($session->all());

        // CSRF Token
        $token = $session->token();
        $this->assertNotEmpty($token);
        $this->assertTrue($session->verifyToken($token));
        $this->assertFalse($session->verifyToken('invalid-token'));

        $newToken = $session->regenerateToken();
        $this->assertNotSame($token, $newToken);
        $this->assertTrue($session->verifyToken($newToken));

        // Flush
        $session->flush();
        $this->assertNull($session->get('user.name'));
    }

    public function test_json_response(): void
    {
        $response = new JsonResponse(['key' => 'value']);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeader('Content-Type'));
        $this->assertSame('{"key":"value"}', $response->getContent());
        $this->assertSame(['key' => 'value'], $response->getData());

        // Merge
        $response->merge(['extra' => 123]);
        $this->assertSame(['key' => 'value', 'extra' => 123], $response->getData());
        $this->assertSame('{"key":"value","extra":123}', $response->getContent());

        // Success envelope
        $success = JsonResponse::success(['token' => 'abc123xyz'], 'Login successful', 200);
        $this->assertSame(200, $success->getStatusCode());
        $data = $success->getData();
        $this->assertTrue($data['success']);
        $this->assertSame('Login successful', $data['message']);
        $this->assertSame(['token' => 'abc123xyz'], $data['data']);

        // Error envelope
        $error = JsonResponse::error('Validation failed', 422, ['email' => ['Invalid email']]);
        $this->assertSame(422, $error->getStatusCode());
        $errData = $error->getData();
        $this->assertFalse($errData['success']);
        $this->assertSame('Validation failed', $errData['message']);
        $this->assertSame(['email' => ['Invalid email']], $errData['errors']);

        // Paginate envelope
        $paginate = JsonResponse::paginate([['id' => 1]], ['total' => 1, 'page' => 1]);
        $pagData = $paginate->getData();
        $this->assertTrue($pagData['success']);
        $this->assertCount(1, $pagData['data']);
        $this->assertSame(1, $pagData['meta']['total']);
    }

    public function test_redirect_response(): void
    {
        $redirect = new RedirectResponse('/dashboard');
        $this->assertSame(302, $redirect->getStatusCode());
        $this->assertSame('/dashboard', $redirect->getTargetUrl());
        $this->assertFalse($redirect->isPermanent());

        $permanent = RedirectResponse::to('/new-url', 301);
        $this->assertSame(301, $permanent->getStatusCode());
        $this->assertTrue($permanent->isPermanent());
        $this->assertSame('/new-url', $permanent->getTargetUrl());

        // Back redirect
        $_SERVER['HTTP_REFERER'] = '/previous-page';
        $back = RedirectResponse::back();
        $this->assertSame('/previous-page', $back->getTargetUrl());

        // Fluent helpers
        $chained = (new RedirectResponse('/login'))
            ->with('status', 'Logged out')
            ->withInput(['email' => 'test@example.com']);
        $this->assertInstanceOf(RedirectResponse::class, $chained);
        $this->assertSame('/login', $chained->getTargetUrl());
    }

    public function test_uploaded_file(): void
    {
        // Mock a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'vel_');
        file_put_contents($tempFile, 'fake image content');

        $uploaded = new UploadedFile(
            'avatar.png',
            'image/png',
            $tempFile,
            18,
            UPLOAD_ERR_OK
        );

        $this->assertSame('avatar.png', $uploaded->getOriginalName());
        $this->assertSame('png', $uploaded->getExtension());
        $this->assertSame('image/png', $uploaded->getMimeType());
        $this->assertSame(18, $uploaded->getSize());
        $this->assertSame(0.02, $uploaded->getSizeInKb());
        $this->assertSame(UPLOAD_ERR_OK, $uploaded->getError());

        // Error message mapping
        $failedUpload = new UploadedFile('test.pdf', 'application/pdf', '', 0, UPLOAD_ERR_INI_SIZE);
        $this->assertFalse($failedUpload->isValid());
        $this->assertStringContainsString('upload_max_filesize', $failedUpload->getErrorMessage());

        // Clean up temp file
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }

    public function test_response_factory(): void
    {
        $factory = new ResponseFactory();

        // Plain make
        $res = $factory->make('Hello World', 200);
        $this->assertInstanceOf(Response::class, $res);
        $this->assertSame('Hello World', $res->getContent());

        // NoContent
        $noContent = $factory->noContent();
        $this->assertSame(204, $noContent->getStatusCode());
        $this->assertSame('', $noContent->getContent());

        // Text & HTML
        $text = $factory->text('Plain text');
        $this->assertStringContainsString('text/plain', $text->getHeader('Content-Type'));

        $html = $factory->html('<h1>Title</h1>');
        $this->assertStringContainsString('text/html', $html->getHeader('Content-Type'));

        // JSON
        $json = $factory->json(['status' => 'ok']);
        $this->assertInstanceOf(JsonResponse::class, $json);
        $this->assertSame('{"status":"ok"}', $json->getContent());

        // Redirect
        $redirect = $factory->redirect('/home');
        $this->assertInstanceOf(RedirectResponse::class, $redirect);
        $this->assertSame('/home', $redirect->getTargetUrl());

        // Success / Error wrappers
        $succ = $factory->success(['foo' => 'bar']);
        $this->assertInstanceOf(JsonResponse::class, $succ);

        $err = $factory->error('Forbidden', 403);
        $this->assertSame(403, $err->getStatusCode());
    }

    public function test_global_helpers(): void
    {
        // response() with no args returns ResponseFactory
        $factory = response();
        $this->assertInstanceOf(ResponseFactory::class, $factory);

        // response() with args returns Response
        $res = response('Test content', 201);
        $this->assertInstanceOf(Response::class, $res);
        $this->assertSame(201, $res->getStatusCode());
        $this->assertSame('Test content', $res->getContent());

        // json() returns JsonResponse
        $json = json(['hello' => 'world']);
        $this->assertInstanceOf(JsonResponse::class, $json);
        $this->assertSame('{"hello":"world"}', $json->getContent());

        // redirect() returns RedirectResponse
        $redirect = redirect('/dashboard');
        $this->assertInstanceOf(RedirectResponse::class, $redirect);
        $this->assertSame('/dashboard', $redirect->getTargetUrl());

        // back() returns RedirectResponse
        $_SERVER['HTTP_REFERER'] = '/origin';
        $back = back();
        $this->assertInstanceOf(RedirectResponse::class, $back);
        $this->assertSame('/origin', $back->getTargetUrl());
    }
}
