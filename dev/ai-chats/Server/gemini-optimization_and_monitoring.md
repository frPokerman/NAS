# PHP scripts execution

> In a PHP Symfony server, are the PHP scripts loaded only once at server start, or are they loaded for each client request?

In a standard PHP Symfony setup, the PHP scripts are **loaded and executed for each individual client request**.

Unlike long-running environments (like Node.js, Java, or Go) where the application stays in memory, PHP traditionally follows a "shared-nothing" architecture: it starts up, processes the request, delivers the response, and then completely shuts down and clears its memory.

However, there is some nuance to how this works in modern PHP, especially with caching mechanisms. Here is how it breaks down:

### 1. The Standard Lifecycle (OPcache)

While the files are read and executed per request, **OPcache** (which is enabled by default in production) changes how they are "loaded":

* **Without OPcache:** PHP reads, parses, and compiles your `.php` scripts into opcode *every single time* a request comes in. This is highly inefficient.
* **With OPcache:** The first time a script is requested, PHP compiles it and stores the resulting **opcode** in shared memory. For all subsequent requests, PHP skips reading and compiling the files altogether and executes the bytecode directly from memory.

So, while the state of the application resets every request, the files themselves aren't being re-read from the hard drive.

---

### 2. How Symfony Optimizes This

Because Symfony is a large framework with thousands of files, compiling everything on the fly would be too slow. Symfony uses a **compiled container** to speed things up:

* During deployment (or the first request in development), Symfony parses all your configuration, routing, and dependency injections.
* It dumps all of this into highly optimized, cached PHP files in the `var/cache/` directory.
* On subsequent requests, Symfony only needs to load these pre-compiled cache files rather than scanning your entire project.

---

### 3. The Exception: Long-Running Symfony (Swoole, RoadRunner, FrankenPHP)

If you *want* Symfony to load only once at server start and stay in memory across requests, you can use modern PHP application servers like **FrankenPHP**, **RoadRunner**, or **Swoole**.

When using these tools:

* The Symfony kernel boots **once** when the server starts.
* The server stays alive in memory.
* When a client request comes in, it is passed directly to the already-running application. This makes Symfony incredibly fast (often 5x–10x faster) but requires you to be very careful about memory leaks and state management.

Are you looking to optimize a traditional Symfony setup, or are you considering migrating to a long-running server like FrankenPHP?

# Low-resources hardware optimization

> My server needs to run (in production) on a 1 GB memory Raspberry PI. In that case, the tradition Symfony is better, right?

For a production server on a **1 GB Raspberry Pi**, you are absolutely on the right track: **the traditional Symfony setup (using PHP-FPM with OPcache) is generally the safer and better choice**, but with a few important caveats.

Here is a breakdown of why the traditional setup wins on low-memory hardware, and how to make sure it doesn't crash your Pi.

---

### Why Traditional Symfony Wins on 1 GB RAM

The biggest constraint on a 1 GB Raspberry Pi isn't usually CPU power; it's **Memory (RAM)**.

* **Predictable Memory Usage:** In a traditional setup (Nginx + PHP-FPM), when a request finishes, PHP completely destroys that process's memory. If a heavy request spikes memory usage, that memory is immediately freed back to the OS.
* **The Danger of Long-Running Workers:** If you used something like FrankenPHP or RoadRunner, your Symfony application stays booted in RAM across multiple worker processes. If your code has even a tiny **memory leak** (e.g., a service that appends data to an array over time, or Doctrine caching entities in memory), the worker's memory will grow until it triggers the Linux Out-Of-Memory (OOM) killer, crashing your server.

---

### How to Optimize Traditional Symfony for 1 GB RAM

While traditional is better, standard PHP-FPM out-of-the-box settings are often tuned for servers with 4 GB+ of RAM. You *must* tweak a few settings to keep it stable on a 1 GB Pi.

#### 1. Limit PHP-FPM Workers (`php-fpm.conf`)

By default, PHP-FPM might spawn 10 to 20 worker processes. If each Symfony request takes about 40–60 MB of RAM, 20 workers will easily blast past your 1 GB limit and crash the Pi.

Change your PHP-FPM pool configuration (usually in `/etc/php/8.x/fpm/pool.d/www.conf`):

```ini
pm = onset / dynamic ; or 'ondemand' for very low traffic
pm.max_children = 5  ; Strict limit! Max 5 concurrent requests
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
pm.max_requests = 500 ; Respawn workers periodically to clear any memory creep

```

*> **Note:** Restricting `max_children` to 5 means if 6 people hit your site at the exact same millisecond, the 6th person waits a few milliseconds for a worker to free up. This keeps your server alive instead of crashing.*

#### 2. Maximize OPcache Preloading

Because you are using traditional PHP, you want OPcache to do heavy lifting. Enable **OPcache Preloading** in your production `php.ini`. Symfony provides a preloader file out of the box (`config/preload.php`). This compiles your most-used Symfony classes into shared memory once when PHP-FPM starts, saving CPU and RAM on every request.

#### 3. Never run `composer install` or cache clearing on the Pi

Composer is incredibly memory-hungry and will almost certainly trigger an Out-Of-Memory error on a 1 GB Pi.

* Build your cache and vendor folder on a separate machine (like your laptop or a CI/CD pipeline).
* Transfer the final, zipped `vendor/` and `var/cache/` directories to the Pi during deployment.

---

### When would a long-running server make sense here?

The only time you should consider FrankenPHP or RoadRunner on a 1 GB Pi is if your traffic is incredibly low (e.g., just you or a few smart-home devices), and you only run **one single worker process** (`max_workers = 1`).

This would give you lightning-fast response times because Symfony never has to reboot, and it would keep memory flat—but the moment your code leaks memory or you get concurrent traffic, a 1 GB system will struggle. Stick to traditional PHP-FPM; it is battle-tested for resource-constrained environments.

# Workers monitoring

> How could I monitor the workers usage over time and see after a given period of time the maximum amount of workers which were busy simultaneously, the average and such statistics?

To monitor your PHP-FPM workers and gather statistics like concurrent busy workers, peaks, and averages, you don't need heavy or expensive software. PHP-FPM actually has a built-in feature designed exactly for this called the **Status Page**.

Combined with a lightweight tool, you can easily track this over time without overwhelming your 1 GB Raspberry Pi.

Here is the best way to set this up.

---

## Step 1: Enable the PHP-FPM Status Page

First, you need to tell PHP-FPM to expose its internal statistics.

1. Open your PHP-FPM pool configuration file (e.g., `/etc/php/8.x/fpm/pool.d/www.conf`).
2. Find the line `;pm.status_path = /status` and remove the semicolon `;` to uncomment it:
```ini
pm.status_path = /status

```


3. Restart PHP-FPM: `sudo systemctl restart php8.x-fpm`.

Now PHP-FPM is generating real-time stats internally, but it's hidden behind your web server.

---

## Step 2: Expose it via Nginx

Next, configure Nginx so you can access this page (ideally protecting it so the public can't see your server stats).

Open your Nginx site configuration file and add this block inside your `server { ... }` block:

```nginx
location /status {
    allow 127.0.0.1;            # Allow local requests (good for scripts)
    allow your_development_ip;  # Optional: Allow your local PC to view it
    deny all;                   # Block everyone else
    
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    fastcgi_index index.php;
    include fastcgi_params;
    fastcgi_pass unix:/run/php/php8.x-fpm.sock; # Adjust to your PHP version socket
}

```

Reload Nginx (`sudo systemctl reload nginx`). If you run `curl http://localhost/status` from inside the Pi, you will see a live output like this:

```text
pool:                 www
process manager:      dynamic
start time:           28/Jun/2026:18:15:00 +0200
start since:          3600
accepted conn:        1250
listen queue:         0
max listen queue:     2
listen queue len:     128
idle processes:       3
active processes:     2      <-- Current busy workers right now
total processes:      5
max active processes: 4      <-- The absolute HIGHEST peak of simultaneous workers since start
max children reached: 0

```

> 💡 **Instant Peak Tracking:** Look at `max active processes`. This explicitly tracks the **maximum amount of workers which were busy simultaneously** since PHP-FPM last started!

---

## Step 3: Tracking History and Averages (Over Time)

While the raw status page shows you the *peak* (`max active processes`), it resets whenever the PHP service restarts, and it doesn't show you averages over a specific week or month. To get historical statistics on a 1 GB Pi, you want something lightweight.

Here are the two best options for your hardware:

### Option A: The Lightweight GUI Approach (Netdata)

**Netdata** is a zero-configuration, incredibly fast monitoring tool. It is written in highly optimized C, making it perfect for a Raspberry Pi because it uses almost no CPU and very little RAM.

1. Install Netdata on your Pi (it takes about 2 minutes).
2. It will automatically detect your Nginx and PHP-FPM status pages.
3. You can open Netdata in your web browser and see beautiful, historical interactive charts showing exactly how many workers were active, idle, and queued over hours, days, or weeks. It will calculate your averages and show you exact traffic spikes visually.

### Option B: The DIY Cron Script (Zero Extra RAM)

If you don't want to install any visualization software at all, you can write a tiny Bash script that queries the status page every minute and logs it to a text/CSV file.

Create a script named `track_workers.sh`:

```bash
#!/bin/bash
# Fetch the status page in a format easy to parse (json)
STATS=$(curl -s http://localhost/status?json)

TIMESTAMP=$(date "+%Y-%m-%d %H:%M:%S")
ACTIVE=$(echo $STATS | grep -o '"active processes":[0-9]*' | cut -d: -f2)
MAX_ACTIVE=$(echo $STATS | grep -o '"max active processes":[0-9]*' | cut -d: -f2)

# Save to a CSV file
echo "$TIMESTAMP,$ACTIVE,$MAX_ACTIVE" >> /var/log/php_worker_stats.csv

```

Set up a Linux **cron job** (`crontab -e`) to run this script every minute:

```text
* * * * * /bin/bash /path/to/track_workers.sh

```

After a given period of time, you can open `php_worker_stats.csv` in Excel or LibreOffice, where you can easily use standard formulas to calculate the average active workers, or filter to see the exact times your server experienced maximum load.

# Apache configuration

> This is very interesting but I actually use Apache instead of Nginx. Furthermore, I would prefer to make the status page "half public" so that moderators can also access to these statistics. Can you show me the equivalent configuration for Apache without restricting the access to localhost? \
\
Now concerning the tracking history option, I would have prefered to have a home-made interface (i.e. the status page). In this case I would go for the Cron solution, unless tools like Netdata provide some sort of API or ways to retrieve the data without loading their status page and programmatically extracting the infos?

### 1. Apache Configuration for the PHP-FPM Status Page

To expose the status page through Apache, you use the `ProxyPassMatch` directive (assuming you are using `mod_proxy_fcgi`, which is standard for PHP-FPM setups).

Instead of locking it down strictly to `127.0.0.1`, you can secure it using **Basic HTTP Authentication** (username and password). This allows your moderators to log in from anywhere without exposing the metrics to the general public.

#### Step A: Create a password file for your moderators

Run this command in your Pi's terminal to create a `.htpasswd` file and add a moderator user (e.g., `moderator1`):

```bash
sudo htpasswd -c /etc/apache2/.htpasswd moderator1

```

*(It will prompt you to type and confirm a password).*

#### Step B: Update your Apache Configuration

Open your Apache VirtualHost configuration file (usually in `/etc/apache2/sites-available/`) and add this block inside the `<VirtualHost *:80>` or `<VirtualHost *:443>` section:

```apache
<Location /status>
    # Forward the request directly to the PHP-FPM pool socket
    ProxyPass "unix:/run/php/php8.x-fpm.sock|fcgi://localhost/status"
    
    # Enable HTTP Basic Authentication
    AuthType Basic
    AuthName "PHP-FPM Status - Moderators Only"
    AuthUserFile /etc/apache2/.htpasswd
    Require valid-user
</Location>

```

*Make sure to change `php8.x-fpm.sock` to match your actual PHP version.*

Reload Apache to apply changes:

```bash
sudo systemctl reload apache2

```

Now, anyone visiting `http://your-server-ip/status` will be prompted for a username and password before they can see the stats.

---

### 2. Can Netdata be used programmatically?

**Yes, absolutely.** Netdata is built entirely around an incredibly rich, lightweight **REST API**. In fact, the Netdata web dashboard itself is just a JavaScript application that makes API requests to its own backend.

This means you don't have to load their heavy dashboard interface. Your home-made Symfony dashboard can query Netdata's API programmatically in the background, fetch the precise metrics you care about, and format them however you like.

#### How Netdata handles data

* Netdata stores metrics in a highly optimized custom database in the Pi's RAM.
* It automatically calculates averages, minimums, maximums, and percentiles for any given time window over the past days/weeks.

#### Example Netdata API call

You can ask Netdata's API for the history of PHP-FPM active workers using a simple `curl` request or PHP's `HttpClient`:

```http
GET http://localhost:19999/api/v1/data?chart=web_log_nginx.php_fpm_workers&after=-3600&points=60&group=average

```

**What this tells Netdata:**

* `chart=...`: Give me the PHP worker metrics.
* `after=-3600`: Look at the last 1 hour (3600 seconds).
* `points=60`: Give me exactly 60 data points back (one per minute).
* `group=average`: Calculate the **average** worker usage for each minute block. (You can change this to `group=max` to get the peak spikes!).

The API responds with a clean, lightweight JSON payload containing the exact timestamps, averages, and peaks.

### Which route should you pick?

* **Go with the Cron + SQLite/CSV solution** if you want 100% full ownership over how data is archived long-term, and you enjoy writing the calculation logic (averages/peaks) directly in your Symfony code.
* **Go with Netdata's API** if you want your custom dashboard to have features like *"Show me the worker graph for last week"* without you having to write complex database aggregation queries. Netdata will do the heavy math instantly for you, and your Symfony app just consumes the JSON result.

# Netdata's database

> You mentioned a custom database used by Netdata. Can you give me more details about it?

Netdata uses a custom, in-house built time-series database engine simply called **`dbengine`**. It was custom-engineered from scratch to solve a major paradox in monitoring: **how do you log thousands of system metrics every single second without destroying the CPU, RAM, and SD card of a low-power device like a Raspberry Pi?**

Traditional databases (like MySQL, InfluxDB, or Prometheus) use background processes like "compaction" and batch jobs to organize data, which can heavily spike CPU and memory usage. Netdata’s `dbengine` takes a completely different approach.

Here is exactly how it works and why it is perfect for your 1 GB Raspberry Pi.

---

### 1. The WORM Architecture (Protects your Pi's SD Card)

The database operates on a **WORM (Write Once, Read Many)** and **append-only** model.

* As metrics (like your PHP workers) flow in every second, they are compressed in memory and appended sequentially to the end of a database file on the disk.
* There are **no database updates** and **no background compaction jobs**.

Because it never goes back to rewrite old data, disk I/O remains incredibly flat, preventing the constant "disk churning" that easily wears out Raspberry Pi micro-SD cards. If the Pi suddenly loses power, data corruption is virtually impossible because the files are append-only.

---

### 2. Intelligent Multi-Tier Downsampling

Netdata manages long-term retention by automatically slicing your data into **multiple storage tiers** on disk. Instead of saving every single second for a whole year, it automatically aggregates (downsamples) the data as it ages:

* **Tier 0 (Per-Second):** The raw data. Highly efficient compression shrinks this down to roughly **0.6 bytes per sample**. By default, it keeps this high-resolution data for days or weeks.
* **Tier 1 (Per-Minute):** Netdata automatically averages the per-second data into 1-minute blocks (a 60x save on space).
* **Tier 2 (Per-Hour):** It downsamples the per-minute data into 1-hour blocks.

Because all tiers are calculated **in parallel** at the exact moment the data is collected (rather than by a heavy batch job hours later), there is no sudden midnight CPU spike on your Pi.

---

### 3. Separation of Retention from RAM

In older versions of Netdata, the longer you wanted to keep data, the more RAM you needed. The modern `dbengine` completely decouples this.

It keeps a tiny **Metrics Registry** in RAM (taking only about 150 bytes per metric to know the start and end times of what's on disk) and a small **Page Cache** for recent data. The actual history sits entirely on the disk. This means your Pi could store **2 years of history** on its disk/SSD, and Netdata's RAM footprint will remain practically identical to storing 2 days of history.

---

### How this benefits your custom Symfony Dashboard

Because Netdata handles all the math, compression, and tiers on its own, your Symfony app gets incredible power out of the box when hitting its API:

* **Sub-second Queries:** When your moderator requests a 30-day graph, Netdata doesn't scan millions of rows. It instantly fetches the data from **Tier 2 (Hourly)** and returns a tiny JSON array in milliseconds.
* **Easy Time Shifts:** Netdata's API functions (like `group=average` or `group=max`) allow your Symfony app to seamlessly ask: *"Give me the maximum amount of concurrent PHP workers per hour over the last 7 days."*

It essentially gives your Raspberry Pi an enterprise-grade time-series data warehouse that runs comfortably in about **80MB to 150MB of RAM**.