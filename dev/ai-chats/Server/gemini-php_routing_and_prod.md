> [...] [Using the PHP default server (`php.exe -S`)] I can access all php and html files without any issue, but when I try to access for example here a css file, I get the file content and below this PHP error: \
\
Fatal error: Uncaught TypeError: Invalid return value: callable object expected, "int" returned from "[project_path]\public\styles\theme.css". in [project_path]\vendor\autoload_runtime.php:12 Stack trace: #0 [project_path]\public\index.php(5): require_once() #1 {main} thrown in [project_path]\vendor\autoload_runtime.php on line 12

That error happens because PHP's built-in server is treating your CSS file as a PHP routing script and trying to execute it!

When you pass a file path at the end of the `php -S` command (like `/path/to/project/public/index.php`), PHP uses it as a **router script** for *every single request*.

If you request a static file like `theme.css`, the PHP server executes your router script (`index.php`). Because of how Symfony's modern entry point works with `autoload_runtime.php`, it ends up reading the `.css` file as if it were a PHP script, causing it to crash with that `TypeError`.

### The Solution

To fix this, you need to use a dedicated router script that checks if the requested file actually exists before handing it over to Symfony.

1. Create a file named `router.php` in your **project root** (or anywhere convenient outside the public folder) and paste this code inside it:

```php
<?php
// router.php

$path = $_SERVER['DOCUMENT_ROOT'] . $_SERVER['SCRIPT_NAME'];

// If the file exists and is not a directory, let PHP serve it directly
if (file_exists($path) && !is_dir($path)) {
    return false; 
}

// Otherwise, fall back to Symfony's front controller
require_once $_SERVER['DOCUMENT_ROOT'] . '/index.php';

```

2. Restart your PHP server by pointing to your `public/` directory with `-t`, but use this new `router.php` at the end:

```bash
php -S localhost:8000 -t /path/to/your/project/public /path/to/your/project/router.php

```

### Why this works:

When you request `theme.css`, the `router.php` script checks if the file exists in the `public/` folder. The `return false;` line tells the PHP server: *"Hey, this is a real static file. Stop executing PHP code and just send the raw CSS file to the browser."*

> I don't get any error when I don't specify any router in the command line (just "php -S localhost:8000 -t /path/to/your/project/public"), and everything seems to work fine. What is the actual difference between specifying a router or not in the command line?

That is a great observation! You've stumbled onto exactly how the PHP built-in server behaves out of the box.

Here is the actual difference between omitting the router script versus providing one:

### 1. Without a Router Script

When you run just `php -S localhost:8000 -t /path/to/project/public`, PHP automatically looks for an `index.php` or `index.html` file in that folder to serve as the default homepage.

* **Static Files:** If you go to `/styles/theme.css`, PHP sees the file exists and serves it perfectly.
* **The Catch (Symfony Routing Breakage):** This setup will only work for your homepage or URLs that explicitly target a PHP file (like `/index.php/about`). If you try to visit a clean Symfony route like `localhost:8000/about` or `localhost:8000/blog`, the PHP server will look for a physical folder or file named `about` inside `public/`. Since it doesn't exist, PHP will return a standard browser **404 Not Found** page, completely bypassing Symfony.

### 2. With a Router Script

When you append a router script (like the `router.php` example), you are overriding PHP's default behavior for missing files.

* **Static Files:** The script checks if the file exists. If it does, it tells PHP to serve it normally.
* **Symfony Routing Integration:** If you visit a clean URL like `localhost:8000/about`, the script realizes there is no physical file named `about`. Instead of giving up and throwing a 404, it forwards the request to Symfony's `index.php`. Symfony then reads the URL and loads your proper Controller and Twig template.

### Summary

If you only need to look at your homepage or assets, omitting the router works fine. But the moment you click a link to another page in your Symfony application, you will need the router script configuration so Symfony can handle those URLs.

> I'm not sure to understand what you are talking about (the "clean Symfony route"): In facts, I have a defined a Controller associated to a route like /api/core/do_stuff and it still seems to work even without specifying a router in the command.

Ah! That makes perfect sense, and you are 100% correct.

What you are experiencing is a very specific, hidden feature of the PHP built-in server. When you request a "clean" URL that doesn't exist (like `/api/core/do_stuff`), the PHP server doesn't immediately give up. It has a built-in fallback mechanism:

1. It checks if `/api/core/do_stuff` exists as a physical folder or file inside the `-t public` directory. It doesn't.
2. Instead of throwing a 404, it looks to see if there is an **`index.php`** file sitting right in the root of that `public/` directory.
3. Since Symfony *does* have an `index.php` there, PHP automatically forwards the request to it and populates the `$_SERVER['PATH_INFO']` with `/api/core/do_stuff`. Symfony reads that data, matches your controller route, and executes it.

### So, why do we ever need a router script?

If it works for your assets and it works for your Symfony routes out of the box, why did I tell you to use a router script?

There is **one major limitation** to PHP's automatic fallback behavior: **URLs with dots in them.**

If you ever define a Symfony route that contains a period—for example, a route like `/api/download/report.json` or `/users/avatar.jpg`—the PHP built-in server's internal fallback logic trips up. When PHP sees a dot in the URL, it assumes you are strictly looking for a physical static file. Since that file doesn't actually exist on your hard drive, it will bypass `index.php` entirely and instantly throw a standard browser **404 Not Found**.

### Summary

* **Without a router:** Works perfectly for assets and *most* Symfony routes, unless a route has a dot (`.`) in the URL path.
* **With a router:** Guarantees that absolutely *every* missing path is gracefully handed over to Symfony, regardless of what the URL looks like.

> So you are suggesting me to write a new router file and then fallback to the default (Symfony) one if the requested resource is a PHP script, but isn't possible to directly edit the Symfony router?

Yes, it is absolutely possible to edit Symfony's `public/index.php` directly!

The reason frameworks don't do this out of the box is that `public/index.php` is intended to be purely the production "front controller" for Symfony. Any code added there runs on every production request, whereas checking for static files is usually the web server's job (like Nginx).

However, since this is your local development environment, you can absolutely add a snippet to the top of `public/index.php` to handle this. It keeps your command clean so you can just run:

```bash
php -S localhost:8000 -t /path/to/project/public /path/to/project/public/index.php

```

### How to edit `public/index.php`

Open your `public/index.php` and add this code at the **very top**, right after the opening `<?php` tag:

```php
<?php

// --- Add this block for the PHP built-in server ---
if (php_sapi_name() === 'cli-server') {
    $path = $_SERVER['DOCUMENT_ROOT'] . $_SERVER['SCRIPT_NAME'];
    if (file_exists($path) && !is_dir($path)) {
        return false; // Tells the PHP server to serve the asset directly
    }
}
// --------------------------------------------------

use App\Kernel;
// ... the rest of your original Symfony code follows

```

### Why this works perfectly:

* **`php_sapi_name() === 'cli-server'`**: This safety check ensures this code *only* runs when you are using `php -S`. If you ever deploy this to a production server with Nginx or Apache, this block is completely ignored, meaning zero performance or security impact.
* **`return false;`**: When a CSS, JS, or image file path is checked, it hits this line. Returning `false` tells the PHP CLI server to stop executing `index.php` immediately and serve the asset file as-is.

> And what would change if I was now running in production? Would the server be different (from the default PHP)?

Yes, in a production environment, the server setup is completely different from the built-in PHP development server.

Here are the main changes when moving to production:

### 1. The Server Software Changes

Instead of the single-threaded `php -S` CLI server, a production environment splits the work between two distinct pieces of software:

* **The Web Server (Nginx or Apache):** This sits at the front line. Its only job is to handle incoming HTTP requests from the internet extremely quickly.
* **The PHP Process Manager (PHP-FPM):** This is a dedicated, multi-threaded background service that *only* executes PHP code when the web server tells it to.

---

### 2. How Static Files are Handled

In production, your asset requests (like `theme.css`) **never touch PHP at all**.

Nginx or Apache handles them directly from the hard drive, which is incredibly fast and secure. For example, in an Nginx configuration, you use a `try_files` rule:

```nginx
location / {
    try_files $uri /index.php$is_args$args;
}

```

* **If the file exists** (e.g., `/styles/theme.css`): Nginx immediately sends it back to the user. PHP is never woken up.
* **If the file does not exist** (e.g., `/api/core/do_stuff`): Nginx forwards the request to **PHP-FPM**, which executes `public/index.php` to trigger Symfony's routing.

---

### 3. What Happens to the Code We Added?

Because of the safety check we added (`php_sapi_name() === 'cli-server'`), that entire block of code is completely ignored in production.

Under Nginx or Apache, `php_sapi_name()` will return `fpm-fcgi` or `apache2handler` instead of `cli-server`. The code will instantly skip past the asset check and jump straight into booting up Symfony, leaving asset management to the web server where it belongs.

> Are there no PHP or Symfony server build for production?

No, there is **no official production-ready web server built into PHP or Symfony**.

Both `php -S` and the Symfony CLI server (`symfony server:start`) are explicitly designed **only for local development and testing**.

Neither PHP nor Symfony creates a separate production server build because doing so would mean re-inventing the wheel. Excellent, hardened open-source web servers like **Nginx** and **Apache** already exist and are incredibly good at what they do.

Here is why PHP and Symfony leave production serving to dedicated tools:

### 1. Security (The biggest reason)

Development servers prioritize convenience over security. They are not audited for enterprise-grade security vulnerabilities or "Denial of Service" (DoS) attacks. For instance, a basic slow-network attack could easily tie up a PHP development server and take your whole site offline. Production servers like Nginx are specifically built to withstand internet traffic and attacks.

### 2. Multi-Threading & Concurrency

The built-in PHP server is optimized to handle requests sequentially (one after another) for a single developer sitting at a laptop. If 50 people tried to access your site at the exact same millisecond on a production server using `php -S`, the server would choke and stall.

### 3. Separation of Concerns

In the professional web ecosystem, the rule of thumb is: **Let specialized software do what it does best.**

* **Nginx/Apache** are masterfully optimized in C/C++ to read static files from a hard drive, handle SSL certificates (HTTPS), manage thousands of concurrent connections, and cache content.
* **PHP** is an interpreter designed masterfully to execute code and talk to databases.

### So, how do people deploy Symfony in production?

Instead of a "Symfony Server," you deploy the raw code to a Linux environment configured with **PHP-FPM** (FastCGI Process Manager) and a web server.

The standard modern production stack looks like this:

1. **The Client (Browser)** sends a request over HTTPS.
2. **Nginx** receives it, terminates the SSL, and serves any CSS/JS files instantly.
3. If the request is for a dynamic page (like `/api/...`), Nginx passes it over a fast local socket to **PHP-FPM**.
4. **PHP-FPM** spins up a pool of workers, executes your Symfony `index.php`, and hands the response back to Nginx to send to the user.