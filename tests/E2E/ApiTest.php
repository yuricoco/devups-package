<?php

namespace Devups\Tests\E2E;

use Devups\Tests\Support\HttpTestCase;

final class ApiTest extends HttpTestCase
{
    public function testHelloEndpoint(): void
    {
        $res = $this->get('api/hello');
        $this->assertSame(200, $res['status']);
        $this->assertTrue($this->json($res)['success']);
        $this->assertNoServerError('GET api/hello');
    }

    public function testFrontHomePageRenders(): void
    {
        $res = $this->get('');
        $this->assertSame(200, $res['status']);
        $this->assertStringContainsString('</html>', $res['body']);
        $this->assertNoServerError('GET /');
    }

    public function testGenericWriteIsRefusedWithoutToken(): void
    {
        $res = $this->request('POST', 'api/update/user?id=1', ['user' => ['username' => 'pirate']], [], true);
        $this->assertSame(401, $res['status']);
        $this->assertFalse($this->json($res)['success']);
        $this->assertNoServerError('POST api/update/user');
    }

    public function testGenericCreateIsRefusedWithoutToken(): void
    {
        $res = $this->request('POST', 'api/create/notification', ['notification' => ['content' => 'x']], [], true);
        $this->assertSame(401, $res['status']);
        $this->assertNoServerError('POST api/create/notification');
    }

    public function testPublicUserDetailDoesNotLeakPersonalData(): void
    {
        $id = \User::create(['username' => 'e2e-public', 'email' => 'e2e@devups.test', 'phonenumber' => '699999999']);
        try {
            $res = $this->get('api/user/detail?id=' . $id);
            $this->assertSame(200, $res['status']);
            $body = $res['body'];
            $this->assertStringNotContainsString('e2e@devups.test', $body);
            $this->assertStringNotContainsString('699999999', $body);
            $this->assertNoServerError('GET api/user/detail');
        } finally {
            \User::find($id)->__delete();
        }
    }

    public function testOpenApiDocumentIsValidJson(): void
    {
        $res = $this->get('documentation/api.php');
        $this->assertSame(200, $res['status']);
        $doc = $this->json($res);
        $this->assertSame('3.0.0', $doc['openapi']);
        $this->assertStringContainsString(PROJECT_NAME, $doc['info']['title']);
        $this->assertNoServerError('GET documentation/api.php');
    }
}
