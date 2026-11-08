<?php

namespace Devups\Tests\Integration;

use Devups\Tests\Support\DatabaseTestCase;

/**
 * Phase 4 (CMS), ETAPE G.2/G.3 : renommage cmstext -> cmscontent,
 * reconstruction imagecms -> cmsresource (fichier directement sur
 * l'entite, plus de dependance a Dv_image/ModuleGallery) et generation
 * propre de Cmscomment (G.1) - voir TODO.md.
 */
final class CmsPhase4Test extends DatabaseTestCase
{
    public function testCmscontentPersistsItsNonTranslatedFields(): void
    {
        $id = \Cmscontent::create(['reference' => 'phase4-ref', 'slug' => 'phase4-slug', 'active' => 1, 'position' => 5]);

        $cmscontent = \Cmscontent::find($id);
        $this->assertSame('phase4-ref', $cmscontent->getReference());
        $this->assertSame('phase4-slug', $cmscontent->getSlug());
        $this->assertEquals(1, $cmscontent->getActive());
        $this->assertEquals(5, $cmscontent->getPosition());
    }

    public function testCmsresourceIsLinkedToItsCmscontent(): void
    {
        $cmscontentId = \Cmscontent::create(['reference' => 'phase4-resource-ref']);

        $resourceId = \Cmsresource::create([
            'cmscontent_id' => $cmscontentId,
            'image' => 'phase4-test.jpg',
            'uploaddir' => 'cms/',
            'width' => 100,
            'height' => 50,
        ]);

        $resource = \Cmsresource::find($resourceId);
        $this->assertSame('phase4-test.jpg', $resource->getImage());
        $this->assertEquals(100, $resource->getWidth());
        $this->assertSame($cmscontentId, $resource->cmscontent->getId());
    }

    public function testCmscommentIsLinkedToItsCmscontentAndUser(): void
    {
        $cmscontentId = \Cmscontent::create(['reference' => 'phase4-comment-ref']);
        $userId = \User::create(['username' => 'phase4-commenter', 'email' => 'phase4@devups.test', 'phonenumber' => '611111111']);

        $commentId = \Cmscomment::create([
            'cmscontent_id' => $cmscontentId,
            'user_id' => $userId,
            'pseudo' => 'phase4-pseudo',
            'comment' => 'un commentaire de test',
            'seen' => 0,
        ]);

        $comment = \Cmscomment::find($commentId);
        $this->assertSame('phase4-pseudo', $comment->getPseudo());
        $this->assertSame('un commentaire de test', $comment->getComment());
        $this->assertSame($cmscontentId, $comment->cmscontent->getId());
        $this->assertSame($userId, $comment->user->getId());
    }
}
