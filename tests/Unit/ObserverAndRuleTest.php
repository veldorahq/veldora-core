<?php

declare(strict_types=1);

namespace Veldora\Framework\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Veldora\Framework\Database\Connection;
use Veldora\Framework\Database\Model;
use Veldora\Framework\Database\Schema\Blueprint;
use Veldora\Framework\Database\Schema\Schema;
use Veldora\Framework\Foundation\Application;
use Veldora\Framework\Validation\Rule;
use Veldora\Framework\Validation\Validator;

// ──────────────────────────────────────────────────────────────────────────────
// Fixtures — Model & Observer
// ──────────────────────────────────────────────────────────────────────────────

class ObsProduct extends Model
{
    protected ?string $table    = 'obs_products';
    protected array   $fillable = ['name', 'price'];
    protected bool    $timestamps = false;
}

class ProductObserver
{
    public static array $log = [];

    public function creating(ObsProduct $product): void
    {
        self::$log[] = 'creating:' . $product->name;
    }

    public function created(ObsProduct $product): void
    {
        self::$log[] = 'created:' . $product->name;
    }

    public function updating(ObsProduct $product): void
    {
        self::$log[] = 'updating:' . $product->name;
    }

    public function updated(ObsProduct $product): void
    {
        self::$log[] = 'updated:' . $product->name;
    }

    public function saving(ObsProduct $product): void
    {
        self::$log[] = 'saving:' . $product->name;
    }

    public function saved(ObsProduct $product): void
    {
        self::$log[] = 'saved:' . $product->name;
    }

    public function deleting(ObsProduct $product): void
    {
        self::$log[] = 'deleting:' . $product->name;
    }

    public function deleted(ObsProduct $product): void
    {
        self::$log[] = 'deleted:' . $product->name;
    }
}

// ──────────────────────────────────────────────────────────────────────────────
// Fixtures — Custom Validation Rules
// ──────────────────────────────────────────────────────────────────────────────

class UppercaseRule implements Rule
{
    public function passes(string $attribute, mixed $value): bool
    {
        return is_string($value) && $value === strtoupper($value);
    }

    public function message(): string
    {
        return 'The :attribute must be uppercase.';
    }
}

class EvenNumberRule implements Rule
{
    public function passes(string $attribute, mixed $value): bool
    {
        return is_numeric($value) && ((int) $value % 2 === 0);
    }

    public function message(): string
    {
        return 'The :attribute must be an even number.';
    }
}

// ──────────────────────────────────────────────────────────────────────────────
// Test Suite
// ──────────────────────────────────────────────────────────────────────────────

class ObserverAndRuleTest extends TestCase
{
    protected Application $app;
    protected Connection  $db;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = new Application(dirname(__DIR__, 2));
        $this->db  = new Connection(['driver' => 'sqlite', 'database' => ':memory:']);
        $this->app->instance(Connection::class, $this->db);
        Schema::setConnection($this->db);

        Schema::create('obs_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('price');
        });

        // Clear per-class event registry so tests are independent
        $ref = new \ReflectionProperty(Model::class, 'events');
        $ref->setAccessible(true);
        $ref->setValue(null, []);

        // Clear static log
        ProductObserver::$log = [];
    }

    // ── Observer Tests ──────────────────────────────────────────────────────

    /** @test */
    public function observe_fires_creating_and_created_on_save(): void
    {
        ObsProduct::observe(ProductObserver::class);

        $product = new ObsProduct(['name' => 'Widget', 'price' => 10]);
        $product->save();

        $this->assertContains('creating:Widget', ProductObserver::$log);
        $this->assertContains('created:Widget',  ProductObserver::$log);
    }

    /** @test */
    public function observe_fires_saving_and_saved_on_save(): void
    {
        ObsProduct::observe(ProductObserver::class);

        $product = new ObsProduct(['name' => 'Gadget', 'price' => 20]);
        $product->save();

        $this->assertContains('saving:Gadget', ProductObserver::$log);
        $this->assertContains('saved:Gadget',  ProductObserver::$log);
    }

    /** @test */
    public function observe_fires_updating_and_updated_on_update(): void
    {
        ObsProduct::observe(ProductObserver::class);

        $product = ObsProduct::create(['name' => 'Gizmo', 'price' => 30]);
        ProductObserver::$log = []; // reset after create

        $product->price = 99;
        $product->save();

        $this->assertContains('updating:Gizmo', ProductObserver::$log);
        $this->assertContains('updated:Gizmo',  ProductObserver::$log);
    }

    /** @test */
    public function observe_fires_deleting_and_deleted_on_delete(): void
    {
        ObsProduct::observe(ProductObserver::class);

        $product = ObsProduct::create(['name' => 'Thing', 'price' => 5]);
        ProductObserver::$log = [];

        $product->delete();

        $this->assertContains('deleting:Thing', ProductObserver::$log);
        $this->assertContains('deleted:Thing',  ProductObserver::$log);
    }

    /** @test */
    public function observe_accepts_object_instance(): void
    {
        ObsProduct::observe(new ProductObserver());

        $product = new ObsProduct(['name' => 'Bolt', 'price' => 1]);
        $product->save();

        $this->assertContains('creating:Bolt', ProductObserver::$log);
    }

    /** @test */
    public function observe_without_hooks_does_not_fail(): void
    {
        $noOpObserver = new class {};
        ObsProduct::observe($noOpObserver);

        $product = new ObsProduct(['name' => 'NoOp', 'price' => 0]);
        $product->save(); // should not throw

        $this->addToAssertionCount(1);
    }

    // ── Custom Validation Rule Tests ────────────────────────────────────────

    /** @test */
    public function custom_rule_passes_when_valid(): void
    {
        $validator = Validator::make(
            ['code' => 'HELLO'],
            ['code' => [new UppercaseRule()]]
        );

        $this->assertTrue($validator->passes());
        $this->assertSame(['code' => 'HELLO'], $validator->validate());
    }

    /** @test */
    public function custom_rule_fails_when_invalid(): void
    {
        $validator = Validator::make(
            ['code' => 'hello'],
            ['code' => [new UppercaseRule()]]
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('code', $validator->errors());
    }

    /** @test */
    public function multiple_custom_rules_can_be_mixed_with_built_in_rules(): void
    {
        $validator = Validator::make(
            ['amount' => 4],
            ['amount' => ['required', 'integer', new EvenNumberRule()]]
        );

        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function custom_rule_fails_alongside_built_in_rules(): void
    {
        $validator = Validator::make(
            ['amount' => 3],
            ['amount' => ['required', 'integer', new EvenNumberRule()]]
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('amount', $validator->errors());
    }

    /** @test */
    public function multiple_custom_rules_all_evaluated(): void
    {
        $validator = Validator::make(
            ['val' => 'hello'],
            ['val' => [new UppercaseRule(), new EvenNumberRule()]]
        );

        $this->assertTrue($validator->fails());
        $this->assertCount(2, $validator->errors()['val']);
    }

    /** @test */
    public function custom_rule_passes_for_even_number(): void
    {
        $validator = Validator::make(
            ['n' => 8],
            ['n' => [new EvenNumberRule()]]
        );
        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function custom_rule_fails_for_odd_number(): void
    {
        $validator = Validator::make(
            ['n' => 7],
            ['n' => [new EvenNumberRule()]]
        );
        $this->assertTrue($validator->fails());
    }
}
