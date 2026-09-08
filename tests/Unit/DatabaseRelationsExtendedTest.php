<?php

declare(strict_types=1);

namespace Veldora\Framework\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Veldora\Framework\Database\Connection;
use Veldora\Framework\Database\Model;
use Veldora\Framework\Database\Relations\HasOneThrough;
use Veldora\Framework\Database\Relations\MorphMany;
use Veldora\Framework\Database\Relations\MorphOne;
use Veldora\Framework\Database\Relations\MorphTo;
use Veldora\Framework\Database\Relations\MorphToMany;
use Veldora\Framework\Database\Relations\MorphedByMany;

// Dummy Models for Testing
class ExtTestSupplier extends Model
{
    protected bool $timestamps = false;
    protected ?string $table = 'suppliers';
    protected array $fillable = ['id', 'name'];

    public function userHistory(): HasOneThrough
    {
        return $this->hasOneThrough(ExtTestHistory::class, ExtTestUser::class, 'supplier_id', 'user_id');
    }
}

class ExtTestUser extends Model
{
    protected bool $timestamps = false;
    protected ?string $table = 'users';
    protected array $fillable = ['id', 'supplier_id', 'name'];

    public function image(): MorphOne
    {
        return $this->morphOne(ExtTestImage::class, 'imageable');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(ExtTestComment::class, 'commentable');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(ExtTestTag::class, 'taggable', 'taggables', 'taggable_id', 'tag_id');
    }
}

class ExtTestHistory extends Model
{
    protected bool $timestamps = false;
    protected ?string $table = 'histories';
    protected array $fillable = ['id', 'user_id', 'details'];
}

class ExtTestImage extends Model
{
    protected bool $timestamps = false;
    protected ?string $table = 'images';
    protected array $fillable = ['id', 'url', 'imageable_id', 'imageable_type'];

    public function imageable(): MorphTo
    {
        return $this->morphTo('imageable');
    }
}

class ExtTestComment extends Model
{
    protected bool $timestamps = false;
    protected ?string $table = 'comments';
    protected array $fillable = ['id', 'body', 'commentable_id', 'commentable_type'];

    public function commentable(): MorphTo
    {
        return $this->morphTo('commentable');
    }
}

class ExtTestTag extends Model
{
    protected bool $timestamps = false;
    protected ?string $table = 'tags';
    protected array $fillable = ['id', 'name'];

    public function users(): MorphedByMany
    {
        return $this->morphedByMany(ExtTestUser::class, 'taggable', 'taggables', 'tag_id', 'taggable_id');
    }
}

class DatabaseRelationsExtendedTest extends TestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $app = new \Veldora\Framework\Foundation\Application(dirname(__DIR__, 2));
        $this->connection = new Connection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $app->instance(Connection::class, $this->connection);

        $pdo = $this->connection->getPdo();
        $pdo->exec('CREATE TABLE suppliers (id INTEGER PRIMARY KEY, name TEXT)');
        $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, supplier_id INTEGER, name TEXT)');
        $pdo->exec('CREATE TABLE histories (id INTEGER PRIMARY KEY, user_id INTEGER, details TEXT)');
        $pdo->exec('CREATE TABLE images (id INTEGER PRIMARY KEY, url TEXT, imageable_id INTEGER, imageable_type TEXT)');
        $pdo->exec('CREATE TABLE comments (id INTEGER PRIMARY KEY, body TEXT, commentable_id INTEGER, commentable_type TEXT)');
        $pdo->exec('CREATE TABLE tags (id INTEGER PRIMARY KEY, name TEXT)');
        $pdo->exec('CREATE TABLE taggables (id INTEGER PRIMARY KEY, tag_id INTEGER, taggable_id INTEGER, taggable_type TEXT)');
    }

    public function test_has_one_through_relation(): void
    {
        $supplier = ExtTestSupplier::create(['id' => 1, 'name' => 'Acme Corp']);
        $user = ExtTestUser::create(['id' => 10, 'supplier_id' => 1, 'name' => 'Alice']);
        $history = ExtTestHistory::create(['id' => 100, 'user_id' => 10, 'details' => 'Created profile']);

        $rel = $supplier->userHistory();
        $this->assertInstanceOf(HasOneThrough::class, $rel);

        $result = $rel->getResults();
        $this->assertInstanceOf(ExtTestHistory::class, $result);
        $this->assertSame(100, (int) $result->id);
        $this->assertSame('Created profile', $result->details);
    }

    public function test_morph_one_and_morph_to_relation(): void
    {
        $user = ExtTestUser::create(['id' => 1, 'name' => 'Bob']);

        $imageRel = $user->image();
        $this->assertInstanceOf(MorphOne::class, $imageRel);

        $image = $imageRel->create(['url' => 'https://example.com/avatar.jpg']);
        $this->assertInstanceOf(ExtTestImage::class, $image);
        $this->assertSame(ExtTestUser::class, $image->imageable_type);
        $this->assertSame(1, (int) $image->imageable_id);

        // ExtTest MorphTo inverse
        $owner = $image->imageable()->getResults();
        $this->assertInstanceOf(ExtTestUser::class, $owner);
        $this->assertSame(1, (int) $owner->id);
        $this->assertSame('Bob', $owner->name);
    }

    public function test_morph_many_relation(): void
    {
        $user = ExtTestUser::create(['id' => 2, 'name' => 'Charlie']);

        $commentsRel = $user->comments();
        $this->assertInstanceOf(MorphMany::class, $commentsRel);

        $comment1 = $commentsRel->create(['body' => 'Great post!']);
        $comment2 = $commentsRel->create(['body' => 'Thanks for sharing']);

        $allComments = $user->comments()->getResults();
        $this->assertCount(2, $allComments);
        $this->assertSame('Great post!', $allComments[0]->body);
        $this->assertSame('Thanks for sharing', $allComments[1]->body);
    }

    public function test_morph_to_many_and_morphed_by_many_relation(): void
    {
        $user = ExtTestUser::create(['id' => 5, 'name' => 'Dave']);
        $tag1 = ExtTestTag::create(['id' => 1, 'name' => 'Developer']);
        $tag2 = ExtTestTag::create(['id' => 2, 'name' => 'OpenSource']);

        $rel = $user->tags();
        $this->assertInstanceOf(MorphToMany::class, $rel);

        // Attach
        $rel->attach([1, 2]);

        $tags = $user->tags()->getResults();
        $this->assertCount(2, $tags);
        $this->assertSame('Developer', $tags[0]->name);
        $this->assertSame('OpenSource', $tags[1]->name);

        // ExtTest MorphedByMany inverse
        $usersForTag1 = $tag1->users()->getResults();
        $this->assertCount(1, $usersForTag1);
        $this->assertSame('Dave', $usersForTag1[0]->name);

        // Detach
        $rel->detach(1);
        $tagsAfterDetach = $user->tags()->getResults();
        $this->assertCount(1, $tagsAfterDetach);
        $this->assertSame('OpenSource', $tagsAfterDetach[0]->name);

        // Sync
        $rel->sync([1]);
        $tagsAfterSync = $user->tags()->getResults();
        $this->assertCount(1, $tagsAfterSync);
        $this->assertSame('Developer', $tagsAfterSync[0]->name);
    }
}
