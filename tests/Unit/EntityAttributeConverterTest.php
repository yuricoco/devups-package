<?php

namespace Devups\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * LOT 1.2/1.3 (v8.0) : EntityAttributeConverter, le convertisseur
 * d'annotations Doctrine (docblocks) en attributs PHP 8, utilise aussi bien
 * pour avoir converti le framework lui-meme (ETAPE 1.2) que par la commande
 * `php devups upgrade:entities` (ETAPE 1.3) sur un projet utilisateur.
 * Fixtures couvrant les formes reellement rencontrees dans Devups (voir
 * TODO.md ETAPE 1.0) : entite simple, entite traduite (identite composite
 * via @Id @ManyToOne + @JoinColumn), relation simple, contrainte unique, et
 * MappedSuperclass.
 */
final class EntityAttributeConverterTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once ROOT . 'dclass/generator/EntityAttributeConverter.php';
    }

    public function testSimpleEntityIsConvertedToAttributes(): void
    {
        $source = <<<'PHP'
<?php

/**
 * @Entity @Table(name="product")
 * */
class Product extends Model
{
    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var int
     * */
    protected $id;
    /**
     * @Column(name="name", type="string" , length=255 )
     * @var string
     **/
    protected $name;
}
PHP;

        [$converted] = \EntityAttributeConverter::convert($source);
        $converted = \EntityAttributeConverter::ensureOrmImport($converted);

        $this->assertStringContainsString('use Doctrine\ORM\Mapping as ORM;', $converted);
        $this->assertStringContainsString('#[ORM\Entity]', $converted);
        $this->assertStringContainsString('#[ORM\Table(name: "product")]', $converted);
        $this->assertStringContainsString('#[ORM\Id]', $converted);
        $this->assertStringContainsString('#[ORM\GeneratedValue]', $converted);
        $this->assertStringContainsString('#[ORM\Column(type: "integer")]', $converted);
        $this->assertStringContainsString('#[ORM\Column(name: "name", type: "string", length: 255)]', $converted);
        $this->assertStringNotContainsString('@Entity', $converted);
        $this->assertStringNotContainsString('@Column', $converted);

        $this->assertSyntacticallyValidPhp($converted);
    }

    public function testMappedSuperclassIsConverted(): void
    {
        $source = <<<'PHP'
<?php

/**
 * @MappedSuperclass
 * Colonnes heritees par les entites qui etendent cette classe.
 */
class ProductCore extends Model
{
    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var int
     * */
    protected $id;
}
PHP;

        [$converted] = \EntityAttributeConverter::convert($source);
        $converted = \EntityAttributeConverter::ensureOrmImport($converted);

        $this->assertStringContainsString('#[ORM\MappedSuperclass]', $converted);
        $this->assertStringContainsString('Colonnes heritees par les entites qui etendent cette classe.', $converted);
        $this->assertStringNotContainsString('@MappedSuperclass', $converted);

        $this->assertSyntacticallyValidPhp($converted);
    }

    public function testRelationWithJoinColumnIsConverted(): void
    {
        $source = <<<'PHP'
<?php

/**
 * @Entity @Table(name="product_resource")
 * */
class Product_resource extends ImageCore
{
    /**
     * @ManyToOne(targetEntity="\Product")
     * @JoinColumn(onDelete="cascade")
     * @var \Product
     */
    public $product;
}
PHP;

        [$converted] = \EntityAttributeConverter::convert($source);
        $converted = \EntityAttributeConverter::ensureOrmImport($converted);

        $this->assertStringContainsString('#[ORM\ManyToOne(targetEntity: "\Product")]', $converted);
        $this->assertStringContainsString('#[ORM\JoinColumn(onDelete: "cascade")]', $converted);
        $this->assertStringNotContainsString('@ManyToOne', $converted);
        $this->assertStringNotContainsString('@JoinColumn', $converted);

        $this->assertSyntacticallyValidPhp($converted);
    }

    /** Entite traduite : identite composite via @Id @ManyToOne + @JoinColumn (meme forme que Cmscontent_lang/Status_lang/...). */
    public function testTranslatedEntityWithCompositeIdentityIsConverted(): void
    {
        $source = <<<'PHP'
<?php

/**
 * @Entity @Table(name="product_lang")
 * */
class Product_lang extends Dv_langCore
{
    /**
     * @Column(name="title", type="string" , length=255 , nullable=true)
     * @var string
     **/
    protected $title;
    /**
     * @Id @ManyToOne(targetEntity="\Product")
     * @JoinColumn(onDelete="cascade")
     * @var \Product
     */
    public $product;
}
PHP;

        [$converted] = \EntityAttributeConverter::convert($source);
        $converted = \EntityAttributeConverter::ensureOrmImport($converted);

        $this->assertStringContainsString('#[ORM\Column(name: "title", type: "string", length: 255, nullable: true)]', $converted);
        $this->assertStringContainsString('#[ORM\Id]', $converted);
        $this->assertStringContainsString('#[ORM\ManyToOne(targetEntity: "\Product")]', $converted);
        $this->assertStringContainsString('#[ORM\JoinColumn(onDelete: "cascade")]', $converted);

        $this->assertSyntacticallyValidPhp($converted);
    }

    /** Contrainte unique multi-colonnes (meme forme que Status/Tree/Tree_item). */
    public function testUniqueConstraintIsConverted(): void
    {
        $source = <<<'PHP'
<?php

/**
 * @Entity @Table(name="product_variant", uniqueConstraints={
 *     @UniqueConstraint(name="uq_product_variant_product_id_sku", columns={"product_id", "sku"})
 * })
 * */
class Product_variant extends Model
{
    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var int
     * */
    protected $id;
}
PHP;

        [$converted] = \EntityAttributeConverter::convert($source);
        $converted = \EntityAttributeConverter::ensureOrmImport($converted);

        $this->assertStringContainsString(
            '#[ORM\Table(name: "product_variant", uniqueConstraints: [new ORM\UniqueConstraint(name: "uq_product_variant_product_id_sku", columns: ["product_id", "sku"])])]',
            $converted
        );
        $this->assertStringNotContainsString('@UniqueConstraint', $converted);

        $this->assertSyntacticallyValidPhp($converted);
    }

    public function testOptionsArgumentIsConvertedToAPhpArray(): void
    {
        $source = <<<'PHP'
<?php

class Product_option
{
    /**
     * @Column(name="status", type="integer" , options={"default": "1"} )
     * @var int
     **/
    protected $status;
}
PHP;

        [$converted] = \EntityAttributeConverter::convert($source);
        $converted = \EntityAttributeConverter::ensureOrmImport($converted);

        $this->assertStringContainsString('options: ["default" => "1"]', $converted);

        $this->assertSyntacticallyValidPhp($converted);
    }

    public function testConversionIsIdempotent(): void
    {
        $source = <<<'PHP'
<?php

/**
 * @Entity @Table(name="product", uniqueConstraints={
 *     @UniqueConstraint(name="uq_product_sku", columns={"sku"})
 * })
 * */
class Product extends Model
{
    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var int
     * */
    protected $id;
    /**
     * @ManyToOne(targetEntity="\Category")
     * @JoinColumn(onDelete="cascade")
     * @var \Category
     */
    public $category;
}
PHP;

        [$firstPass, $firstCount] = \EntityAttributeConverter::convert($source);
        $this->assertGreaterThan(0, $firstCount);

        [$secondPass, $secondCount] = \EntityAttributeConverter::convert($firstPass);

        $this->assertSame(0, $secondCount, "un deuxieme passage ne doit plus rien convertir (idempotence)");
        $this->assertSame($firstPass, $secondPass);
    }

    private function assertSyntacticallyValidPhp(string $code): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'dv_eac_');
        file_put_contents($tmp, $code);
        $output = [];
        $exitCode = 0;
        exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($tmp) . ' 2>&1', $output, $exitCode);
        unlink($tmp);

        $this->assertSame(0, $exitCode, "code genere invalide :\n" . implode("\n", $output) . "\n\n" . $code);
    }
}
