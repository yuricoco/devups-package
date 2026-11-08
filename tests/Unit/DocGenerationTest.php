<?php

namespace Devups\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * ETAPE N.1/N.2 : dv_build_openapi_spec() / dv_build_doc_guide() - extraits
 * de documentation/api.php et documentation/guide.php (parsing statique,
 * aucune classe PHP chargee) pour etre partages avec `php devups api:doc`
 * et `php devups doc:guide`. Pas de base de donnees necessaire.
 */
final class DocGenerationTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once ROOT . 'dclass/generator/OpenApiGenerator.php';
        require_once ROOT . 'dclass/generator/DocGuideGenerator.php';
    }

    public function testBuildOpenapiSpecReturnsAWellFormedDocument(): void
    {
        $spec = dv_build_openapi_spec(ROOT);

        $this->assertSame('3.0.0', $spec['openapi']);
        $this->assertNotEmpty($spec['paths']);
        $this->assertArrayHasKey('/user/googleAuth', $spec['paths']);
        $this->assertArrayHasKey('post', $spec['paths']['/user/googleAuth']);
    }

    public function testBuildOpenapiSpecIsDeterministic(): void
    {
        $this->assertSame(dv_build_openapi_spec(ROOT), dv_build_openapi_spec(ROOT));
    }

    public function testBuildOpenapiSpecWithoutAdminOmitsSessionAuthAndAdminPaths(): void
    {
        $spec = dv_build_openapi_spec(ROOT);

        $this->assertArrayNotHasKey('SessionAuth', $spec['components']['securitySchemes']);
    }

    public function testBuildOpenapiSpecWithAdminAddsSessionRoutesAndSessionAuth(): void
    {
        $withAdmin = dv_build_openapi_spec(ROOT, true);
        $withoutAdmin = dv_build_openapi_spec(ROOT);

        $this->assertArrayHasKey('SessionAuth', $withAdmin['components']['securitySchemes']);
        $this->assertGreaterThan(count($withoutAdmin['paths']), count($withAdmin['paths']));

        $tagNames = array_column($withAdmin['tags'], 'name');
        $this->assertContains('Admin – session', $tagNames);
    }

    public function testBuildDocGuideReturnsComponentsServicesAndAuth(): void
    {
        $guide = dv_build_doc_guide(ROOT);

        $this->assertArrayHasKey('components', $guide);
        $this->assertArrayHasKey('services', $guide);
        $this->assertArrayHasKey('auth', $guide);
        $this->assertNotEmpty($guide['components']);

        $found = false;
        foreach ($guide['components'] as $component)
            foreach ($component['modules'] as $module)
                foreach ($module['entities'] as $entity)
                    if ($entity['key'] === 'reportingmodel')
                        $found = true;
        $this->assertTrue($found, 'Reportingmodel doit apparaitre dans le guide');
    }

    public function testRenderDocGuideEntityPageProducesHtmlWithoutError(): void
    {
        $guide = dv_build_doc_guide(ROOT);
        $entity = $guide['components'][0]['modules'][0]['entities'][0];

        $html = dv_render_doc_guide_entity_page($guide, $entity);

        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString($entity['name'], $html);
    }
}
