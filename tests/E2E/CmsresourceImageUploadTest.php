<?php

namespace Devups\Tests\E2E;

use Devups\Tests\Support\HttpTestCase;
use Devups\Tests\Support\HttpServer;

/**
 * ETAPE V.4 : trouve en testant l'application reelle de bout en bout -
 * uploader une image sur un Cmscontent (admin/api/cmsresource/create)
 * declenchait un "Warning: Array to string conversion" dans DBAL.php
 * (ligne ~564, au INSERT) : Cmsresource::uploadImage() affectait
 * $url['file']['imagesize'] (le tableau brut de getimagesize(), pas un
 * nombre) en PRIORITE a $this->size (colonne "size", type float) au lieu
 * de $url['file']['size'] (la taille en octets). L'ordre du ?? etait
 * inverse par rapport au meme code, correct, dans
 * Tree_item_image::uploadImage().
 *
 * Un second bug (plus grave) se trouvait juste a cote : quand
 * CmsresourceController::createAction() appelle
 * "CmsresourceTable::init()->router()->getSingleRowRest($cmsresource)"
 * (SANS argument sur init() - le cas normal apres une creation), le
 * Datatable construit n'a jamais d'entite dans son constructeur, donc
 * $this->qbcustom reste null (Datatable::__construct()). Le
 * try/catch(\Exception) de Datatable::getSingleRowRest() etait cense
 * absorber ce genre d'echec, mais "->where()" sur null leve un \Error
 * (pas une \Exception) en PHP8 : non rattrape, 500 fatal a chaque upload
 * reussi. Memes causes, bug independant de l'ordre size/imagesize
 * ci-dessus - les deux sont fixes ici et verifies par ce meme test.
 */
final class CmsresourceImageUploadTest extends HttpTestCase
{
    private ?int $cmscontentId = null;
    private ?int $cmsresourceId = null;
    private ?string $uploadedImage = null;

    protected function tearDown(): void
    {
        $pdo = \Model::pdo();
        if ($this->cmsresourceId !== null) {
            if ($this->uploadedImage) {
                \Dfile::deleteFile($this->uploadedImage, 'cms/');
                \Dfile::deleteFile('150_' . $this->uploadedImage, 'cms/');
            }
            $pdo->exec("DELETE FROM cmsresource WHERE id = " . $this->cmsresourceId);
        }
        if ($this->cmscontentId !== null) {
            $pdo->exec("DELETE FROM cmscontent_lang WHERE cmscontent_id = " . $this->cmscontentId);
            $pdo->exec("DELETE FROM cmscontent WHERE id = " . $this->cmscontentId);
        }
        parent::tearDown();
    }

    public function testUploadingAnImageSucceedsWithoutErrorAndStoresANumericSize(): void
    {
        $pdo = \Model::pdo();
        $pdo->exec("INSERT INTO cmscontent (reference, active, position) VALUES ('etape_v4_regression_upload', 0, 0)");
        $this->cmscontentId = (int)$pdo->lastInsertId();

        $this->loginAdmin();

        $tmpImage = tempnam(sys_get_temp_dir(), 'dvups_test_') . '.png';
        imagepng(imagecreatetruecolor(10, 10), $tmpImage);

        HttpServer::newErrors();

        $ch = curl_init(HttpServer::baseUrl() . '/admin/api/cmsresource/create');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_COOKIEJAR => $this->cookieJarPath(),
            CURLOPT_COOKIEFILE => $this->cookieJarPath(),
            CURLOPT_POSTFIELDS => [
                'cmscontent' => (string)$this->cmscontentId,
                'image' => new \CURLFile($tmpImage, 'image/png', 'etape_v4_regression.png'),
            ],
        ]);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        @unlink($tmpImage);

        $errors = HttpServer::newErrors();

        $this->assertSame(200, $status);
        $this->assertStringNotContainsString('Array to string conversion', (string)$body, "reponse :\n" . $body);
        $this->assertStringNotContainsString('Call to a member function where() on null', (string)$body, "reponse :\n" . $body);
        $this->assertSame([], $errors, "erreurs PHP sur cmsresource/create :\n" . implode("\n", $errors));

        $data = json_decode((string)$body, true);
        $this->assertIsArray($data, "reponse JSON attendue, recu :\n" . substr((string)$body, 0, 500));
        $this->assertTrue($data['success'] ?? false, "upload echoue :\n" . $body);

        $this->cmsresourceId = (int)$data['cmsresource']['id'];
        $this->uploadedImage = $data['cmsresource']['image'];

        $this->assertIsNumeric($data['cmsresource']['size'], "'size' doit etre un nombre (octets), pas le tableau de getimagesize()");
    }
}
