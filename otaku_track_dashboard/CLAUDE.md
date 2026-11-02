# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Architecture & Structure
- **Core App**: PHP-based anime/manga tracking web application ("Otaku Track").
- **Entry Point**: `otaku_track/index.php` and `otaku_track/detail.php`.
- **Services**: `otaku_track/app/services/AniListService.php` (GraphQL) & `otaku_track/app/services/JikanService.php` (REST API).
- **Database**: MySQL with PDO (`otaku_track/config/database.php`, schemas in `otaku_track/database/`).
- **Caching**: Filesystem caching with TTL and circuit breaker (`otaku_track/app/services/ApiGuard.php`).

## Commands
- **Run PHP Server**: `php -S localhost:8000 -t otaku_track index.php`
- **Health Check**: `php -r "require 'otaku_track/app/bootstrap.php'; try { db()->query('SELECT 1'); echo 'DB OK\n'; } catch(Throwable $e) { echo $e->getMessage(); }"`
