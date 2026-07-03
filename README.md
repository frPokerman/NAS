# NAS

A NAS server with additional plugins.

___

Hi, hi! Welcome on this ambitious project!

You may be lost but in case you're not, I am a "junior" developer (regarding PHP: not even born) and I am making here just a (not at all) simple NAS server (not even sure what that means).

If you're reading this, then you are ~~definitively lost~~ watching the very beginning of this wonderful project that (I hope) will be very useful for me, but who knows? maybe for you too.

___

The project idea is to have a **portable server** (and router) where you can store **musics, videos, documents *etc...*** and that you can **bring with you** wherever you go on whatever mean of transport (don't watch videos while driving though), that you can connect to with (quite) all your devices to **stream, share, work, log, or search in your docs without connecting to Internet**.

The goal? **Avoid steaming again and again** the same musics (or videos) from accross the world and beyond (satellites), **connect all your devices** together without handing your private life to Internet on a silver plate, or view the local map or record your journey in a deep forest, a desert, underwater or in the sky!

The cost? I don't know the minimum configuration yet, but it should be able to run on a 1GB (RAM) Raspberry. That is all you need to take the hand back on your devices.

**Expand your network into an ecosystem.**

## 🎯 Latest version

>   Version number: 26.1.3 \
    Date: 2026-07-03

Add services called file lists to provide to controllers and other services information on the content of a specific directory, *e.g.* for listing configurations files.

### 📅 Planned releases

| Release | Features                    |
| ------- | --------------------------- |
| 26.2    | The Settings plugin         |
| 26.3    | Tests                       |
| 26.5    | Changelogs                  |
| 26.10   | Documentation               |

___

## 🚩 Installation and configuration

### 🖥️ Hardware requirements

I don't know the minimum configuration yet, but I personally use a 1GB (RAM) Raspberry Pi 4 B for my own, so:

| Requirement | Minimum                | Recommanded           |
| ----------: | :--------------------: | :-------------------: |
| OS          | Anything that runs PHP | Raspberry Pi OS       |
| CPU         | ?                      | Quad core Cortex-A72  |
| RAM         | ?                      | 1 GB                  |
| Storage     | 4 GB                   | 64 GB (musics, videos)|
| Wi-Fi       | ?                      | 2.4 GHz / Ethernet    |

### 📦 Installation

*To be determined.*

## 🔌 Plugins showcase

Plugins are services that can be enabled (or disabled) depending on your needs and your resources. They add new powerful and tailored tools in addition to the main functionality of serving files.

![Plugins list preview](dev/design/T2Plugins.png)

> Notes: Plugins are presented here as illustration and are likely to change during development.

### 🔩 Core plugins

These plugins can not be disabled. At this point, just uninstall the program... The program doesn't do anything without any other plugin.

* **Shell** - The command line interface of the server.
* **Settings** - The configuration center of the core program and every other plugins.

### 🛞 Native plugins

These plugins are enabled by default as they may be useful for the majority of users, but can easily be disabled if needed.

* **Files** - A storage for your medias and documents to share accross devices or for your backups.
* **Interface** - A graphical user interface (for browsers).
* **Resources** - A monitoring center for resources usage and statistics.
* **Search** - A powerful search bar to find everything on your server.
* **Network** - A Wi-Fi network (proxy) you can connect to with your devices. Can be used as a DNS server, a network-wide advertising blocker, or your personal VPN.
* **Snippets** - "Everything can be simplified". Easy shortcuts to automate your daily tasks (may not do the chores).

### 🔗 Additional plugins

These plugins may not be suited for everyone's use but will surely find their fans.

* **Profiles** - Separates the files and behaviors depending on who is connected (accounts).
* **Messages** - A all-in-one inbox for all your messages.
* **Passwords** - A credential manager.
* **Projects** - Developer tools to host, deploy, test and backup all projects.

## 📚 Enabling / disabling a plugin

*To be determined.*

## 📖 Creating a plugin

*To be determined.*

## 🤝 Contributing

[Creating a plugin](#-creating-a-plugin) is already a huge contribution, but if you are interested in taking an active part in the project, contact me first (see [credits](#-credits)) and I will be happy to give you all necessary access to the project.

If you want to report an issue or make a suggestion, you are more than welcome to use the GitHub Issues section of the project.

Finally, if you prefer suggesting your own idea of the project, feel free to fork the repository and edit my work as you wish.

## 🎓 Credits

I don't like credits.

Although and if you want, you can contact me at santfals@gmail.com.

By the way: I am a certified human being. As long as I will be the only person working on this project, I can guarantee that not a single line of code (and text) has been generated by an artificial intelligence without being read, tested and approved by me, the human.