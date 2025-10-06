<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/oratakashi/Uangku-BE/actions"><img src="https://github.com/oratakashi/Uangku-BE/actions/workflows/docker-image.yml/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Basic Setup

### Generate APP KEY

```
php artisan key:generate
```

### Configure JWT Services

#### Publish Config
```
php artisan vendor:publish --provider="PHPOpenSourceSaver\JWTAuth\Providers\LaravelServiceProvider"
```

#### Generate Key
```
php artisan jwt:secret
```

### Service Library

```
php artisan vendor:publish --provider="LaravelEasyRepository\LaravelEasyRepositoryServiceProvider" --tag="easy-repository-config"
```

### Filament

```
php artisan vendor:publish --tag=filament-config
```

### Laravel Debugbar

```
php artisan vendor:publish --provider="Barryvdh\Debugbar\ServiceProvider"
```

## Docker & CI/CD

### Docker Images

The project uses automated Docker builds with the following tags:

- **Production (latest)**: Built from git tags, multi-architecture (AMD64 + ARM64)
  - `oratakashi/uangku-be:latest`
  - `ghcr.io/uangkuid/uangku-be:latest`

- **Development (dev)**: Built from main branch, AMD64 only
  - `oratakashi/uangku-be:dev`
  - `ghcr.io/uangkuid/uangku-be:dev`

### Running with Docker Compose

#### Development Environment
```bash
docker-compose -f docker-compose-dev.yaml up
```

#### Production Environment
```bash
docker-compose up
```

### Release Process

To create a new release:
```bash
git tag v1.0.0
git push origin v1.0.0
```

This will automatically:
- Build multi-architecture Docker images (AMD64 + ARM64)
- Tag the images as `latest` and `v1.0.0`
- Push to both Docker Hub and GitHub Container Registry

For more details, see [CI-CD-CHANGES.md](CI-CD-CHANGES.md) and [WORKFLOW-DECISION-TREE.md](WORKFLOW-DECISION-TREE.md).
