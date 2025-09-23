<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/uangkuid/Uangku-BE/actions"><img src="https://github.com/uangkuid/Uangku-BE/actions/workflows/docker-image.yml/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## 🚀 CI/CD Optimization

This project features an optimized CI/CD pipeline that has been reduced from **39 minutes** to **8-15 minutes**:

### ⚡ Fast Development Builds
- **AMD64-only builds** for main branch pushes (~8-15 minutes)
- **Multi-architecture builds** only for releases (~25-35 minutes)
- **PR builds** with fast feedback using dedicated workflow

### 📦 Docker Optimizations  
- **Multi-stage Dockerfile** with dependency caching
- **Comprehensive .dockerignore** to reduce build context
- **Registry caching** for faster subsequent builds
- **Security improvements** with non-root user

### 🏗️ Workflow Features
- **Conditional multi-arch** builds (ARM64 + AMD64 only when needed)
- **Enhanced caching** strategy for Docker layers and Composer
- **Parallel registry pushes** to Docker Hub and GHCR
- **Build summaries** with performance metrics

### 🔧 Available Workflows
- `docker-image.yml` - Main build (fast AMD64 for pushes)
- `docker-fast-build.yml` - PR builds (AMD64 only)  
- `release.yml` - Production releases (multi-architecture)

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
