<p align="center">
  <img src="https://raw.githubusercontent.com/veldorahq/veldora/main/docs-site/public/favicon.svg" width="80" height="80" alt="Veldora Logo">
</p>

<h1 align="center">Veldora Framework Core</h1>

<p align="center">
  <strong>The Expressive, High-Performance PHP 8.2+ Web Framework Core.</strong>
</p>

<p align="center">
  <a href="https://packagist.org/packages/veldora/framework"><img src="https://img.shields.io/badge/php-8.2%20%7C%208.3-777bb4.svg?style=flat-square" alt="PHP Version"></a>
  <a href="https://github.com/veldorahq/veldora-core/blob/main/LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square" alt="License"></a>
  <a href="https://github.com/veldorahq/veldora-core"><img src="https://img.shields.io/badge/version-0.4.0-7c6ef5.svg?style=flat-square" alt="Version"></a>
</p>

---

## ⚡ Overview

**`veldora/framework`** is the foundation core package of the **Veldora PHP Framework**. It delivers an elegant syntax, modular architecture, blazing fast template compilation, a powerful CLI engine, native session authentication, and database migrations with zero unnecessary dependencies.

- 🚀 **Blazing Fast**: Lightweight architecture with minimal overhead.
- 🎨 **Modern Template Engine**: Native `.veldora.php` compilation with Blade-style directives and `<x-component>` tag syntax.
- 🗄️ **Query Builder & Active Record**: Clean, chainable database queries and Model layer.
- 🛠️ **Full CLI Engine**: 41+ built-in commands for generators, database migrations, UI components, and development server.
- 🔒 **Security First**: Native CSRF protection, secure password hashing, prepared SQL statements, and session auth.
- 🧩 **Service Container**: Flexible IoC container with automatic dependency resolution.

---

## 📦 Installation

Install the framework core via Composer:

```bash
composer require veldora/framework
```

Or initialize a new full-stack Veldora application in seconds:

```bash
composer create-project veldora/veldora my-app
# or using npx
npx create-veldora-app my-app
```

---

## 🏗️ Core Architecture & Features

### 1. 🛣️ Expressive Routing

Define clean routes with parameters, named routes, middleware groups, and controllers:

```php
use Veldora\Framework\Http\Route;
use App\Controllers\PostController;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::prefix('/posts')->middleware('auth')->group(function () {
    Route::get('/', [PostController::class, 'index']);
    Route::get('/create', [PostController::class, 'create']);
    Route::post('/', [PostController::class, 'store']);
    Route::get('/{id}', [PostController::class, 'show']);
});
```

---

### 2. 🎨 Template Engine (`.veldora.php`)

Veldora compiles clean templates into cached native PHP:

```html
@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Welcome, {{ Auth::user()->name }}</h1>

    @if (session()->has('success'))
        <x-alert variant="success" :message="session('success')" />
    @endif

    <div class="grid">
        @foreach ($posts as $post)
            <x-card :title="$post->title">
                <p>{{ $post->excerpt }}</p>
                <x-button href="/posts/{{ $post->id }}" variant="primary">Read More</x-button>
            </x-card>
        @endforeach
    </div>
</div>
@endsection
```

---

### 3. 🗄️ Database & Models

Work with fluent queries or Active Record models:

```php
namespace App\Models;

use Veldora\Framework\Database\Model;

class Post extends Model
{
    protected string $table = 'posts';
    protected array $fillable = ['title', 'slug', 'body', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

// Queries
$posts = Post::where('status', 'published')->orderBy('created_at', 'desc')->get();
$post  = Post::create([
    'title' => 'Getting Started with Veldora',
    'body'  => 'Veldora makes PHP fun again.',
]);
```

---

### 4. 📜 Schema & Migrations

Define database tables programmatically:

```php
use Veldora\Framework\Database\Migrations\Migration;
use Veldora\Framework\Database\Schema\Blueprint;
use Veldora\Framework\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('body');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
```

Run migrations via CLI:

```bash
php veldora migrate
php veldora migrate:rollback
php veldora migrate:status
```

---

### 5. ⚡ CLI Tooling (`bin/veldora`)

Over 41 built-in development commands:

```bash
php veldora serve                # Start local development server
php veldora make:controller      # Scaffold a new Controller
php veldora make:model           # Scaffold a new Model
php veldora make:migration       # Scaffold a new Migration
php veldora make:middleware      # Scaffold a new Middleware
php veldora add [component]      # Install a UI component (e.g., button, datatable, modal)
php veldora route:list           # Display registered application routes
php veldora view:clear           # Clear compiled view cache
```

---

## 🧪 Testing

Run framework unit and integration test suites:

```bash
composer test
```

---

## 🤝 Contributing

We welcome contributions from the community! Please read our [Contributing Guidelines](CONTRIBUTING.md) and [Code of Conduct](CODE_OF_CONDUCT.md) before submitting pull requests.

---

## 🛡️ Security

If you discover any security vulnerabilities in Veldora Core, please review our [Security Policy](SECURITY.md).

---

## 📄 License

Veldora Framework Core is open-sourced software licensed under the **[MIT License](LICENSE)**.

Copyright &copy; 2026 **Shahriyar Fahim** / **Veldora Authors**.
